#!/usr/bin/env python3
"""Minimal websocket client to exercise the brisk channel.

It does a challenge login, asks for the upgrade on index_rd_wss.php and reads
frames for a few seconds, reporting how many arrive and when the connection is
closed. It tells a server defect apart from the state of one browser tab.
"""
import base64
import hashlib
import os
import socket
import ssl
import sys
import time
import urllib.parse

HOST = sys.argv[1] if len(sys.argv) > 1 else "127.0.0.1"
PORT = int(sys.argv[2]) if len(sys.argv) > 2 else 8444
USER = sys.argv[3] if len(sys.argv) > 3 else "load390"
PASSWD = sys.argv[4] if len(sys.argv) > 4 else None
SECS = float(sys.argv[5]) if len(sys.argv) > 5 else 20.0
PFX = "/brisk/"
if PASSWD is None:
    PASSWD = USER


def conn():
    s = socket.create_connection((HOST, PORT), 10)
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx.wrap_socket(s, server_hostname=HOST)


def get(path, cookie=None):
    c = conn()
    req = "GET %s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\n" % (path, HOST)
    if cookie:
        req += "Cookie: sess=%s\r\n" % cookie
    req += "\r\n"
    c.sendall(req.encode())
    buf = b""
    while True:
        try:
            d = c.recv(65536)
        except Exception:
            break
        if not d:
            break
        buf += d
    c.close()
    return buf


def login():
    chal = get("%sindex_wr.php?mesg=getchallenge&cli_name=%s" % (PFX, USER))
    tok = chal.rsplit(b"|", 1)[-1].strip().decode()
    mp = hashlib.md5(PASSWD.encode()).hexdigest()
    priv = hashlib.md5((tok + mp).encode()).hexdigest()
    body = get("%sindex.php?name=%s&pass_private=%s" % (PFX, USER, priv))
    import re
    m = re.search(rb'sess = "([0-9a-f]+)"', body)
    return m.group(1).decode() if m else None


def ws(sess):
    key = base64.b64encode(os.urandom(16)).decode()
    c = conn()
    path = ("%sindex_rd_wss.php?sess=%s&stat=&subst=&step=-1&from=index_php"
            "&transp=websocketsec" % (PFX, sess))
    req = ("GET %s HTTP/1.1\r\nHost: %s\r\nUpgrade: websocket\r\n"
           "Connection: Upgrade\r\nSec-WebSocket-Key: %s\r\n"
           "Sec-WebSocket-Version: 13\r\nCookie: sess=%s\r\n\r\n"
           % (path, HOST, key, sess))
    c.sendall(req.encode())
    c.settimeout(SECS)
    head = b""
    while b"\r\n\r\n" not in head:
        d = c.recv(4096)
        if not d:
            print("  connection closed during the handshake")
            return
        head += d
    status = head.split(b"\r\n", 1)[0].decode(errors="replace")
    print("  answer to the upgrade: %s" % status)
    rest = head.split(b"\r\n\r\n", 1)[1]
    if b"101" not in status.encode():
        print("  body received: %d bytes (not a websocket)" % len(rest))
        return

    nbytes = len(rest)
    nframes = 0
    t0 = time.monotonic()
    while time.monotonic() - t0 < SECS:
        try:
            d = c.recv(65536)
        except socket.timeout:
            break
        except Exception as e:
            print("  read error: %s" % e)
            break
        if not d:
            print("  closed by the server after %.1f s and %d bytes" % (
                time.monotonic() - t0, nbytes))
            return
        nbytes += len(d)
        nframes += d.count(b"\x81") + d.count(b"\x82")
    print("  stayed open %.1f s, %d bytes received, ~%d frames" % (
        time.monotonic() - t0, nbytes, nframes))
    c.close()


s = login()
print("session: %s" % s)
if s:
    ws(s)
