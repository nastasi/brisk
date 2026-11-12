#!/usr/bin/env python3
"""Burst of simultaneous connections on the unix sockets of the daemon.

It measures how many the listen queue refuses (EAGAIN) when they all arrive
together: the case nginx meets when many players come in at the same instant.
"""
import socket
import sys

pfx = sys.argv[1] if len(sys.argv) > 1 else "/home/brisk/priv/brisk"
npool = int(sys.argv[2]) if len(sys.argv) > 2 else 10
n = int(sys.argv[3]) if len(sys.argv) > 3 else 200
only0 = len(sys.argv) > 4 and sys.argv[4] == "single"

ok = eagain = other = 0
keep = []
for i in range(n):
    idx = 0 if only0 else i % npool
    s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    s.setblocking(False)
    try:
        s.connect("%s%d.sock" % (pfx, idx))
        ok += 1
        keep.append(s)
    except BlockingIOError:
        # a non blocking connect on a unix socket either succeeds at once or
        # fails: EAGAIN here means the listen queue is full
        eagain += 1
        s.close()
    except OSError as e:
        if e.errno == 11:
            eagain += 1
        else:
            other += 1
        s.close()

print("  out of %d simultaneous connections: %d accepted, %d refused (queue full), %d other errors"
      % (n, ok, eagain, other))
for s in keep:
    s.close()
