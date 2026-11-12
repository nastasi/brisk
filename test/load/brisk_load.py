#!/usr/bin/env python3
"""Load generator for brisk.

Simulates N players in the room: each one comes in as a guest, keeps the comet
stream of index_rd.php open and now and then writes in the chat with
index_wr.php.

It measures the latency of the writes (which is what the player feels) and how
well the read streams hold. A single process, asyncio: the generator has to be
cheap, because it runs on the same machine as the daemon.
"""

import argparse
import asyncio
import hashlib
import random
import re
import ssl
import statistics
import sys
import time
import zlib

SESS_RE = re.compile(rb'sess = "([0-9a-f]+)"')


class Stats:
    def __init__(self):
        self.write_lat = []      # write latencies, in ms
        self.write_err = 0
        self.login_err = 0
        self.stream_bytes = 0
        self.stream_deliv = 0    # chat messages delivered to the readers
        self.stream_reconn = 0
        self.stream_err = 0
        self.stream_raw = 0      # bytes on the wire, before inflating
        self.framing_err = 0     # chunks that did not add up
        self.inflate_err = 0     # compressed streams that did not inflate
        self.enc_err = 0         # answers that did not carry the coding asked for
        self.http_err = {}       # status code -> count

    def http(self, code):
        self.http_err[code] = self.http_err.get(code, 0) + 1


class Dechunker:
    """Incremental reader of the chunked framing. It hands back only whole
    chunks, and raises as soon as one does not add up: a chunk shorter than
    its declared length, or one that is not closed by its CRLF, is the defect
    this bench is here to catch."""

    def __init__(self):
        self.buf = b""
        self.need = None
        self.done = False

    def feed(self, data):
        self.buf += data
        out = []
        while not self.done:
            if self.need is None:
                i = self.buf.find(b"\r\n")
                if i < 0:
                    break
                head = self.buf[:i].split(b";")[0]
                try:
                    n = int(head, 16)
                except ValueError:
                    raise ValueError("chunk length unreadable: %r" % head[:24])
                self.buf = self.buf[i + 2:]
                if n == 0:
                    self.done = True
                    break
                self.need = n
            if len(self.buf) < self.need + 2:
                break
            if self.buf[self.need:self.need + 2] != b"\r\n":
                raise ValueError("chunk of %d bytes not closed by CRLF" % self.need)
            out.append(self.buf[:self.need])
            self.buf = self.buf[self.need + 2:]
            self.need = None
        return b"".join(out)


class Client:
    def __init__(self, idx, host, port, use_tls, prefix, stats, auth=False,
                 encoding="plain"):
        self.idx = idx
        self.encoding = encoding
        if auth:
            self.name = "load%03d" % idx
            self.passwd = self.name
        else:
            self.name = "Ospite%04d" % idx
            self.passwd = None
        self.host = host
        self.port = port
        self.use_tls = use_tls
        self.prefix = prefix
        self.st = stats
        self.sess = None
        self.step = 0

    async def _open(self):
        ctx = None
        if self.use_tls:
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
        return await asyncio.open_connection(self.host, self.port, ssl=ctx)

    async def _fetch(self, path, timeout=30):
        """A request with Connection: close: the response ends with EOF, so the
        latency is the time up to the last byte."""
        r, w = await self._open()
        try:
            req = ("GET %s HTTP/1.1\r\nHost: %s\r\n"
                   "Connection: close\r\nUser-Agent: brisk-load\r\n" % (path, self.host))
            if self.sess:
                req += "Cookie: sess=%s\r\n" % self.sess
            req += "\r\n"
            w.write(req.encode())
            await w.drain()
            data = await asyncio.wait_for(r.read(), timeout)
            return data
        finally:
            w.close()
            try:
                await w.wait_closed()
            except Exception:
                pass

    async def login(self):
        path = "%sindex.php?name=%s" % (self.prefix, self.name)
        if self.passwd is not None:
            # challenge login: the token has to be combined with the md5 of
            # the password, exactly as j_login_manager() does in the real client
            try:
                chal = await self._fetch("%sindex_wr.php?mesg=getchallenge&cli_name=%s"
                                         % (self.prefix, self.name))
                tok = chal.rsplit(b"|", 1)[-1].strip().decode()
            except Exception:
                self.st.login_err += 1
                return False
            mp = hashlib.md5(self.passwd.encode()).hexdigest()
            priv = hashlib.md5((tok + mp).encode()).hexdigest()
            path += "&pass_private=" + priv
        try:
            body = await self._fetch(path)
        except Exception:
            self.st.login_err += 1
            return False
        m = SESS_RE.search(body)
        if not m:
            self.st.login_err += 1
            code = body.split(b"\r\n", 1)[0].split(b" ")[1:2]
            if code:
                self.st.http(code[0].decode(errors="replace"))
            return False
        self.sess = m.group(1).decode()
        return True

    async def reader(self, stop):
        """The comet stream: it reopens when the server closes it, as the real
        client does."""
        while not stop.is_set():
            try:
                r, w = await self._open()
            except Exception:
                self.st.stream_err += 1
                await asyncio.sleep(1)
                continue
            try:
                req = ("GET %sindex_rd.php?stat=&subst=&step=-1&from=index_php&transp=xhr"
                       " HTTP/1.1\r\nHost: %s\r\nCookie: sess=%s\r\n"
                       "User-Agent: brisk-load\r\n" % (self.prefix, self.host, self.sess))
                if self.encoding != "plain":
                    req += "Accept-Encoding: %s\r\n" % self.encoding
                req += "\r\n"
                w.write(req.encode())
                await w.drain()

                head = b""
                while b"\r\n\r\n" not in head:
                    d = await asyncio.wait_for(r.read(65536), 120)
                    if not d:
                        raise EOFError("closed during the headers")
                    self.st.stream_raw += len(d)
                    head += d
                head, _, rest = head.partition(b"\r\n\r\n")
                low = head.lower()
                if self.encoding == "plain":
                    if b"content-encoding:" in low:
                        self.st.enc_err += 1
                elif ("content-encoding: %s" % self.encoding).encode() not in low:
                    self.st.enc_err += 1

                dech = Dechunker()
                # gzip carries its own header, deflate the zlib wrapper of
                # RFC 1950: the window tells them apart
                inf = (zlib.decompressobj(zlib.MAX_WBITS | 16) if self.encoding == "gzip"
                       else zlib.decompressobj(zlib.MAX_WBITS) if self.encoding == "deflate"
                       else None)

                def consume(raw):
                    body = dech.feed(raw)
                    if not body:
                        return
                    text = inf.decompress(body) if inf else body
                    self.st.stream_bytes += len(text)
                    self.st.stream_deliv += text.count(b"chatt_sub(")

                consume(rest)
                while not stop.is_set():
                    chunk = await asyncio.wait_for(r.read(65536), 120)
                    if not chunk:
                        break
                    self.st.stream_raw += len(chunk)
                    consume(chunk)
            except asyncio.TimeoutError:
                self.st.stream_err += 1
            except ValueError:
                self.st.framing_err += 1
            except zlib.error:
                self.st.inflate_err += 1
            except Exception:
                self.st.stream_err += 1
            finally:
                w.close()
                try:
                    await w.wait_closed()
                except Exception:
                    pass
            if not stop.is_set():
                self.st.stream_reconn += 1
                await asyncio.sleep(0.3)

    async def writer(self, stop, period):
        await asyncio.sleep(random.uniform(0, period))
        while not stop.is_set():
            mesg = "chatt%%7Ccarico%d" % random.randint(0, 99999)
            path = "%sindex_wr.php?sess=%s&stp=%d&mesg=%s" % (
                self.prefix, self.sess, self.step, mesg)
            self.step += 1
            t0 = time.monotonic()
            try:
                body = await self._fetch(path, timeout=30)
                dt = (time.monotonic() - t0) * 1000.0
                head = body.split(b"\r\n", 1)[0]
                if b" 200" in head:
                    self.st.write_lat.append(dt)
                else:
                    self.st.write_err += 1
                    self.st.http(head.decode(errors="replace")[:32])
            except Exception:
                self.st.write_err += 1
            await asyncio.sleep(period * random.uniform(0.8, 1.2))


async def run(args):
    st = Stats()
    stop = asyncio.Event()
    clients = [Client(args.base + i, args.host, args.port, args.tls, args.prefix, st,
                      auth=args.auth, encoding=args.encoding)
               for i in range(args.clients)]

    # staggered entry: a wave of simultaneous logins would measure a transient,
    # not the steady state
    t0 = time.monotonic()
    ok = 0
    for i in range(0, len(clients), args.batch):
        group = clients[i:i + args.batch]
        res = await asyncio.gather(*[c.login() for c in group])
        ok += sum(1 for r in res if r)
        await asyncio.sleep(args.ramp)
    print("  logins succeeded: %d/%d in %.1fs" % (ok, len(clients), time.monotonic() - t0),
          flush=True)
    if ok == 0:
        return st, 0

    live = [c for c in clients if c.sess]
    tasks = []
    for c in live:
        tasks.append(asyncio.create_task(c.reader(stop)))
        if not args.silent:
            tasks.append(asyncio.create_task(c.writer(stop, args.period)))

    await asyncio.sleep(args.warmup)
    st.write_lat.clear()          # the transient does not count
    st.write_err = 0
    base_bytes = st.stream_bytes
    base_raw = st.stream_raw
    base_deliv = st.stream_deliv
    t1 = time.monotonic()

    await asyncio.sleep(args.duration)
    dur = time.monotonic() - t1
    st.stream_bytes -= base_bytes
    st.stream_raw -= base_raw
    st.stream_deliv -= base_deliv

    stop.set()
    for t in tasks:
        t.cancel()
    await asyncio.gather(*tasks, return_exceptions=True)
    return st, dur


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--host", default="127.0.0.1")
    p.add_argument("--port", type=int, default=8082)
    p.add_argument("--tls", action="store_true")
    p.add_argument("--prefix", default="/brisk/")
    p.add_argument("--clients", type=int, default=25)
    p.add_argument("--base", type=int, default=1)
    p.add_argument("--batch", type=int, default=10)
    p.add_argument("--ramp", type=float, default=0.5)
    p.add_argument("--warmup", type=float, default=20)
    p.add_argument("--duration", type=float, default=60)
    p.add_argument("--period", type=float, default=10)
    p.add_argument("--auth", action="store_true", help="registered users (load001...)")
    p.add_argument("--silent", action="store_true", help="streams only, no writes")
    p.add_argument("--encoding", choices=("plain", "gzip", "deflate"), default="plain",
                   help="content coding to ask for on the stream")
    args = p.parse_args()

    st, dur = asyncio.run(run(args))
    lat = sorted(st.write_lat)
    def pct(q):
        if not lat:
            return float("nan")
        return lat[min(len(lat) - 1, int(len(lat) * q))]

    print("  writes: %d in %.0fs (%.1f/s)" % (len(lat), dur, len(lat) / dur if dur else 0))
    if lat:
        print("  latency ms: p50 %.1f  p95 %.1f  p99 %.1f  max %.1f  mean %.1f" % (
            pct(.50), pct(.95), pct(.99), lat[-1], statistics.fmean(lat)))
    print("  errors: writes %d, logins %d, streams %d" % (
        st.write_err, st.login_err, st.stream_err))
    print("  streams: %.0f KB read (%.0f KB/s), %d messages delivered (%.0f/s), %d reopenings" % (
        st.stream_bytes / 1024.0, st.stream_bytes / 1024.0 / dur if dur else 0,
        st.stream_deliv, st.stream_deliv / dur if dur else 0, st.stream_reconn))
    print("  coding %s: %.0f KB on the wire%s" % (
        args.encoding, st.stream_raw / 1024.0,
        "" if args.encoding == "plain" or not st.stream_raw
        else " (%.1f%% of the %.0f KB inflated)" % (
            100.0 * st.stream_raw / st.stream_bytes if st.stream_bytes else 0,
            st.stream_bytes / 1024.0)))
    print("  stream integrity: framing %d, inflate %d, coding announced %d" % (
        st.framing_err, st.inflate_err, st.enc_err))
    if st.http_err:
        print("  unexpected responses: %s" % st.http_err)
    sys.stdout.flush()


if __name__ == "__main__":
    main()
