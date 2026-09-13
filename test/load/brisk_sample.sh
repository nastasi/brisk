#!/bin/bash
# Samples the resources of daemon and frontend during a load test.
# usage: brisk_sample.sh <seconds> <interval> <label>
DUR="${1:-60}"; INT="${2:-5}"; TAG="${3:-x}"
. "$(dirname "$0")/common.sh"

dpid="$(pgrep -f 'php \./brisk-spush\.php' | head -1)"
if [ -z "$dpid" ]; then echo "daemon not found"; exit 1; fi

# cumulative cpu of a set of processes, in seconds
cpusec() { local tot=0 t; for p in $*; do
    t=$(awk '{print ($14+$15)/'"$(getconf CLK_TCK)"'}' /proc/$p/stat 2>/dev/null || echo 0)
    tot=$(echo "$tot $t" | awk '{print $1+$2}'); done; echo "$tot"; }

# the frontend: nginx (direct mode) or apache (descriptor handover)
fpids() { { pgrep -f 'nginx: worker'; pgrep -x apache2; } | tr '\n' ' '; }

d0=$(cpusec $dpid); f0=$(cpusec $(fpids)); t0=$(date +%s.%N)
maxrss=0; maxfd=0; maxconn=0; n=0
end=$(( $(date +%s) + DUR ))
while [ $(date +%s) -lt $end ]; do
    sleep "$INT"
    rss=$(awk '/VmRSS/{print $2}' /proc/$dpid/status 2>/dev/null || echo 0)
    fd=$($SUDO ls /proc/$dpid/fd 2>/dev/null | wc -l)
    conn=$(ss -x 2>/dev/null | grep -c 'brisk[0-9]*\.sock')
    [ "$rss" -gt "$maxrss" ] && maxrss=$rss
    [ "$fd" -gt "$maxfd" ] && maxfd=$fd
    [ "$conn" -gt "$maxconn" ] && maxconn=$conn
    n=$((n+1))
done
d1=$(cpusec $dpid); f1=$(cpusec $(fpids)); t1=$(date +%s.%N)

echo "$d0 $d1 $f0 $f1 $t0 $t1 $maxrss $maxfd $maxconn" | awk '{
  el = $6 - $5;
  printf "  daemon: cpu %.0f%% of one core, rss %.0f MB, %d open fds\n", ($2-$1)/el*100, $7/1024, $8;
  printf "  frontend: cpu %.0f%% of one core (every process)\n", ($4-$3)/el*100;
  printf "  unix connections towards the daemon (max): %d\n", $9;
}'
