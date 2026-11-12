#!/usr/bin/env python3
"""Half a request, and then silence: the daemon has to give up on it.

It opens two connections to a daemon socket and leaves both of them
hanging, without writing any more and without closing:

  head    half of the head of a GET, the empty line never comes
  body    a whole POST head, Content-Length larger than the body sent

The daemon keeps each one in a pending page, waiting for the rest
(PENDINGPAGE_WAITHEAD, PENDINGPAGE_WAITDATA). With the defect those pages
never expired - try_flush returned before looking at the deadline - and
they held their socket until the daemon restarted. Now the daemon closes
them at the deadline of the page, 20 seconds: the script waits for the EOF
on both, and reports how long it took.

  halfhead.py [socket] [wait]
"""
import socket
import sys
import time

PFX = "/brisk/"
SOCK = sys.argv[1] if len(sys.argv) > 1 else "/home/brisk/priv/brisk0.sock"
WAIT = float(sys.argv[2]) if len(sys.argv) > 2 else 40.0

HALF = {
    "head": ("GET %sindex.php HTTP/1.1\r\nHost: local" % PFX).encode(),
    "body": ("POST %sindex_wr.php HTTP/1.1\r\nHost: localhost\r\n"
             "Content-Type: application/x-www-form-urlencoded\r\n"
             "Content-Length: 100\r\n\r\nmesg=" % PFX).encode(),
}

conns = {}
for name, data in HALF.items():
    s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    s.connect(SOCK)
    s.sendall(data)
    s.setblocking(False)
    conns[name] = s
t0 = time.monotonic()
print("  two half requests sent, waiting up to %.0f s for the daemon to close them"
      % WAIT)

closed = {}
while len(closed) < len(conns) and time.monotonic() - t0 < WAIT:
    for name, s in conns.items():
        if name in closed:
            continue
        try:
            d = s.recv(4096)
        except BlockingIOError:
            continue
        except OSError:
            d = b""
        if not d:
            closed[name] = time.monotonic() - t0
            print("  %s: closed by the daemon after %.1f s" % (name, closed[name]))
    time.sleep(0.2)

for s in conns.values():
    s.close()
missing = [n for n in conns if n not in closed]
if missing:
    print("  still open after %.0f s: %s" % (WAIT, ", ".join(missing)))
    sys.exit(1)
print("  both given up by the daemon")
