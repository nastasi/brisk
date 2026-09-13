#!/usr/bin/env python3
"""Two streams on the same session: the first one must be dismissed.

It opens a stream, opens a second one with the same session and looks at what
the first receives before being closed. Before the fix it received only the
closing of the socket, and the client on the other side reopened forever.
"""
import base64
import hashlib
import os
import re
import socket
import ssl
import sys
import time

HOST, PORT, PFX = "127.0.0.1", 8444, "/brisk/"
USER = sys.argv[1] if len(sys.argv) > 1 else "load350"
TRANSP = sys.argv[2] if len(sys.argv) > 2 else "xhr"


def conn():
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    return ctx.wrap_socket(socket.create_connection((HOST, PORT), 10), server_hostname=HOST)


def get(path, cookie=None):
    c = conn()
    req = "GET %s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\n" % (path, HOST)
    if cookie:
        req += "Cookie: sess=%s\r\n" % cookie
    c.sendall((req + "\r\n").encode())
    buf = b""
    while True:
        d = c.recv(65536)
        if not d:
            break
        buf += d
    c.close()
    return buf


chal = get("%sindex_wr.php?mesg=getchallenge&cli_name=%s" % (PFX, USER))
tok = chal.rsplit(b"|", 1)[-1].strip().decode()
priv = hashlib.md5((tok + hashlib.md5(USER.encode()).hexdigest()).encode()).hexdigest()
m = re.search(rb'sess = "([0-9a-f]+)"',
              get("%sindex.php?name=%s&pass_private=%s" % (PFX, USER, priv)))
sess = m.group(1).decode()
print("  session: %s, transport of the first stream: %s" % (sess, TRANSP))


def apri(transp):
    c = conn()
    if transp.startswith("websocket"):
        key = base64.b64encode(os.urandom(16)).decode()
        req = ("GET %sindex_rd_wss.php?sess=%s&stat=&subst=&step=-1&from=index_php&transp=%s"
               " HTTP/1.1\r\nHost: %s\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
               "Sec-WebSocket-Key: %s\r\nSec-WebSocket-Version: 13\r\nCookie: sess=%s\r\n\r\n"
               % (PFX, sess, transp, HOST, key, sess))
    else:
        req = ("GET %sindex_rd.php?sess=%s&stat=&subst=&step=-1&from=index_php&transp=%s"
               " HTTP/1.1\r\nHost: %s\r\nCookie: sess=%s\r\n\r\n"
               % (PFX, sess, transp, HOST, sess))
    c.sendall(req.encode())
    return c


primo = apri(TRANSP)
primo.settimeout(6)
iniz = b""
t0 = time.monotonic()
while time.monotonic() - t0 < 4:
    try:
        d = primo.recv(65536)
    except socket.timeout:
        break
    if not d:
        break
    iniz += d
print("  first stream open: %d bytes of initialisation" % len(iniz))

secondo = apri("xhr")
print("  second stream opened on the same session")

primo.settimeout(10)
congedo = b""
chiuso = False
t0 = time.monotonic()
while time.monotonic() - t0 < 8:
    try:
        d = primo.recv(65536)
    except socket.timeout:
        break
    if not d:
        chiuso = True
        break
    congedo += d

print("  the first stream received %d bytes, then %s"
      % (len(congedo), "closed by the server" if chiuso else "no close"))
print("     stops the stream (xstm.stop):  %s" % (b"xstm.stop" in congedo))
print("     shows the notice (new notify): %s" % (b"new notify" in congedo))
print("     sends back to the login:       %s" % (b"location.assign" in congedo))
if TRANSP.startswith("websocket") and congedo:
    print("     first byte 0x%02x (0x81 = text frame)" % congedo[0])
primo.close()
secondo.close()
