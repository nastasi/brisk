#!/usr/bin/env python3
"""Hangs up while the daemon prepares a response, and tells whether it survives.

It sends a login to a daemon socket and closes the connection a few
milliseconds later, while the daemon is still checking the password on the
database: it is what nginx does when the browser goes away in the middle of
a request (proxy_ignore_client_abort off, the default).

With the defect, the write of the response failed on the closed peer and
returned FALSE, which try_flush took for a short write of zero bytes: the
page was parked with its socket. At the next turn the read loop found the
socket at EOF, closed it and left the page in the list, and the following
flush wrote on a closed resource - a TypeError from php 8 on, and the
daemon died.

The close has to land after the daemon has read the request and before it
writes the answer, so the delay is swept over a range, round after round;
after each one it checks that the daemon still answers.

  hangup.py [socket] [user] [rounds]

The users are those of README: loadNNN, password equal to the name.
"""
import hashlib
import socket
import sys
import time

PFX = "/brisk/"
SOCK = sys.argv[1] if len(sys.argv) > 1 else "/home/brisk/priv/brisk0.sock"
USER = sys.argv[2] if len(sys.argv) > 2 else "load390"
ROUNDS = int(sys.argv[3]) if len(sys.argv) > 3 else 60


def request(path):
    return ("GET %s%s HTTP/1.1\r\nHost: localhost\r\nConnection: close\r\n\r\n"
            % (PFX, path)).encode()


def get(path):
    s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    s.settimeout(10)
    s.connect(SOCK)
    s.sendall(request(path))
    buf = b""
    while True:
        d = s.recv(65536)
        if not d:
            break
        buf += d
    s.close()
    return buf


def alive():
    try:
        return get("index.php").startswith(b"HTTP/1.1 200")
    except OSError:
        return False


def login_path():
    tok = get("index_wr.php?mesg=getchallenge&cli_name=%s" % USER)
    tok = tok.rsplit(b"|", 1)[-1].strip().decode()
    priv = hashlib.md5((tok + hashlib.md5(USER.encode()).hexdigest()).encode()).hexdigest()
    return "index.php?name=%s&pass_private=%s" % (USER, priv)


for i in range(ROUNDS):
    delay = (i % 20) * 0.001          # 0 .. 19 ms, then again
    path = login_path()
    s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    s.connect(SOCK)
    s.sendall(request(path))
    time.sleep(delay)
    s.close()                         # gone while the daemon is working
    time.sleep(0.2)                   # the daemon sees the EOF and flushes again
    if not alive():
        print("  round %d (close after %.0f ms): the daemon DOES NOT ANSWER"
              % (i + 1, delay * 1000))
        sys.exit(1)
print("  the daemon survived %d hang-ups" % ROUNDS)
