#!/usr/bin/env python3
"""Access by the same user from a second browser (different sessions).

The first stream must be sent back to the login, where ghost_sess explains
that the session was assigned to another browser.
"""
import hashlib, re, socket, ssl, sys, time
HOST, PORT, PFX = "127.0.0.1", 8444, "/brisk/"
USER = sys.argv[1] if len(sys.argv) > 1 else "load310"

def conn():
    ctx = ssl.create_default_context(); ctx.check_hostname=False; ctx.verify_mode=ssl.CERT_NONE
    return ctx.wrap_socket(socket.create_connection((HOST, PORT), 10), server_hostname=HOST)

def get(path, cookie=None):
    c = conn()
    req = "GET %s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\n" % (path, HOST)
    if cookie: req += "Cookie: sess=%s\r\n" % cookie
    c.sendall((req + "\r\n").encode())
    buf = b""
    while True:
        d = c.recv(65536)
        if not d: break
        buf += d
    c.close(); return buf

def login():
    chal = get("%sindex_wr.php?mesg=getchallenge&cli_name=%s" % (PFX, USER))
    tok = chal.rsplit(b"|", 1)[-1].strip().decode()
    priv = hashlib.md5((tok + hashlib.md5(USER.encode()).hexdigest()).encode()).hexdigest()
    m = re.search(rb'sess = "([0-9a-f]+)"', get("%sindex.php?name=%s&pass_private=%s" % (PFX, USER, priv)))
    return m.group(1).decode() if m else None

def stream(sess):
    c = conn()
    c.sendall(("GET %sindex_rd.php?sess=%s&stat=&subst=&step=-1&from=index_php&transp=xhr HTTP/1.1\r\n"
               "Host: %s\r\nCookie: sess=%s\r\n\r\n" % (PFX, sess, HOST, sess)).encode())
    return c

a = login(); print("  first access:   %s" % a)
sa = stream(a); sa.settimeout(5)
t0 = time.monotonic(); iniz = b""
while time.monotonic() - t0 < 3:
    try: d = sa.recv(65536)
    except socket.timeout: break
    if not d: break
    iniz += d
print("  first stream open (%d bytes)" % len(iniz))

b = login(); print("  second access:  %s  (session %s)" % (b, "different" if b != a else "THE SAME"))
sb = stream(b)
time.sleep(1)

sa.settimeout(8); cong = b""
t0 = time.monotonic()
while time.monotonic() - t0 < 6:
    try: d = sa.recv(65536)
    except socket.timeout: break
    if not d: break
    cong += d
print("  the first stream received %d bytes" % len(cong))
print("     stops the stream:    %s" % (b"xstm.stop" in cong))
print("     goes back to login:  %s" % (b"location.assign" in cong))
print("     only shows a notice: %s" % (b"new notify" in cong))
sa.close(); sb.close()
