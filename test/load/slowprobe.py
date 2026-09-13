#!/usr/bin/env python3
"""Slow client: it connects, stops reading while a burst happens in the room,
then resumes.

If in the meantime the server queued more than COMM_N messages, the history is
lost and the client is sent back to the initial page (splash included). That is
recognised from the markers of the full initialisation, prefs_load() and
notify_ex(), which in normal operation arrive only once.
"""
import hashlib
import re
import socket
import ssl
import sys
import time

HOST = "127.0.0.1"
PORT = 8444
USER = sys.argv[1] if len(sys.argv) > 1 else "load370"
PAUSA = float(sys.argv[2]) if len(sys.argv) > 2 else 45.0
PFX = "/brisk/"


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
mp = hashlib.md5(USER.encode()).hexdigest()
priv = hashlib.md5((tok + mp).encode()).hexdigest()
body = get("%sindex.php?name=%s&pass_private=%s" % (PFX, USER, priv))
m = re.search(rb'sess = "([0-9a-f]+)"', body)
if not m:
    print("login fallito")
    sys.exit(1)
sess = m.group(1).decode()
print("  session: %s" % sess)

c = conn()
req = ("GET %sindex_rd.php?stat=&subst=&step=-1&from=index_php&transp=xhr"
       " HTTP/1.1\r\nHost: %s\r\nCookie: sess=%s\r\n\r\n" % (PFX, HOST, sess))
c.sendall(req.encode())

# phase 1: read the initialisation
c.settimeout(8)
iniz = b""
t0 = time.monotonic()
while time.monotonic() - t0 < 6:
    try:
        d = c.recv(65536)
    except socket.timeout:
        break
    if not d:
        break
    iniz += d
print("  initialisation: %d bytes, prefs_load: %d" % (
    len(iniz), iniz.count(b"prefs_load(")))

# phase 2: stop reading; the burst happens now
print("  not reading for %.0f seconds..." % PAUSA)
sys.stdout.flush()
time.sleep(PAUSA)

# phase 3: resume
c.settimeout(15)
dopo = b""
t0 = time.monotonic()
while time.monotonic() - t0 < 12:
    try:
        d = c.recv(65536)
    except socket.timeout:
        break
    if not d:
        break
    dopo += d
c.close()

reinit = dopo.count(b"prefs_load(")
print("  resumed: %d bytes, room updates: %d, reinitialisations: %d"
      % (len(dopo), dopo.count(b"j_stand_cont("), reinit))
print("  OUTCOME: %s" % ("HISTORY LOST, sent back to the initial page" if reinit
                       else "history preserved"))
