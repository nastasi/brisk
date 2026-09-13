#!/usr/bin/env python3
"""Forces a partial write on the daemon and checks what comes out.

It logs in through nginx, then talks to one of the daemon unix sockets
directly (which is what nginx itself does in direct http mode) with a tiny
SO_RCVBUF and without reading for a while: the daemon's fwrite() cannot place
the whole initial payload and returns short.

Then it reads everything and validates the HTTP chunked framing plus the
@BEGIN@...@END@ blocks the javascript client looks for. A truncated chunk or
a block without its terminator means the resumption after the partial write
is corrupt.
"""
import hashlib
import re
import socket
import ssl
import sys
import time

HOST, PORT, PFX = "127.0.0.1", 8444, "/brisk/"
USER = sys.argv[1] if len(sys.argv) > 1 else "load390"
SOCK = sys.argv[2] if len(sys.argv) > 2 else "/home/brisk/priv/brisk0.sock"
RCVBUF = int(sys.argv[3]) if len(sys.argv) > 3 else 2048
PAUSA = float(sys.argv[4]) if len(sys.argv) > 4 else 6.0


def https(path):
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    c = ctx.wrap_socket(socket.create_connection((HOST, PORT), 10), server_hostname=HOST)
    c.sendall(("GET %s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\n\r\n"
               % (path, HOST)).encode())
    buf = b""
    while True:
        d = c.recv(65536)
        if not d:
            break
        buf += d
    c.close()
    return buf


chal = https("%sindex_wr.php?mesg=getchallenge&cli_name=%s" % (PFX, USER))
tok = chal.rsplit(b"|", 1)[-1].strip().decode()
priv = hashlib.md5((tok + hashlib.md5(USER.encode()).hexdigest()).encode()).hexdigest()
m = re.search(rb'sess = "([0-9a-f]+)"',
              https("%sindex.php?name=%s&pass_private=%s" % (PFX, USER, priv)))
if not m:
    print("login failed")
    sys.exit(1)
sess = m.group(1).decode()
print("  session: %s" % sess)

u = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
u.setsockopt(socket.SOL_SOCKET, socket.SO_RCVBUF, RCVBUF)
u.connect(SOCK)
req = ("GET %sindex_rd.php?sess=%s&stat=&subst=&step=-1&from=index_php&transp=xhr"
       " HTTP/1.1\r\nHost: %s\r\nCookie: sess=%s\r\n\r\n" % (PFX, sess, HOST, sess))
u.sendall(req.encode())
print("  request sent, not reading for %.0f s (SO_RCVBUF=%d)" % (PAUSA, RCVBUF))
time.sleep(PAUSA)

u.settimeout(8)
buf = b""
t0 = time.monotonic()
while time.monotonic() - t0 < 20:
    try:
        d = u.recv(4096)
    except socket.timeout:
        break
    if not d:
        break
    buf += d
    time.sleep(0.05)          # slow reader: keeps the pressure on
u.close()
print("  received: %d bytes" % len(buf))

head, _, body = buf.partition(b"\r\n\r\n")
print("  status: %s" % head.split(b"\r\n")[0].decode(errors="replace"))
print("  declares chunked: %s" % (b"chunked" in head))

# walk the chunked framing
pos, chunks, errore = 0, 0, None
while pos < len(body):
    fine = body.find(b"\r\n", pos)
    if fine < 0:
        errore = "chunk header truncated at %d" % pos
        break
    try:
        ln = int(body[pos:fine].split(b";")[0], 16)
    except ValueError:
        errore = "chunk length unreadable at %d: %r" % (pos, body[pos:fine][:40])
        break
    if ln == 0:
        chunks += 1
        break
    if fine + 2 + ln + 2 > len(body):
        errore = ("chunk of %d bytes declared but only %d available (truncated)"
                  % (ln, len(body) - fine - 2))
        break
    pos = fine + 2 + ln + 2
    chunks += 1

print("  well formed chunks: %d" % chunks)
print("  FRAMING: %s" % ("BROKEN -> " + errore if errore else "consistent"))

aperti = body.count(b"@BEGIN@")
chiusi = body.count(b"@END@")
print("  @BEGIN@ %d / @END@ %d -> %s"
      % (aperti, chiusi, "paired" if aperti == chiusi else "UNPAIRED"))
