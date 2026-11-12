#!/bin/bash
# Measures what a client receives while 150 users come into the room.
# Subprocess loops outlive their parent: without this they are left orphaned,
# spinning for nothing (it happened: eighty loops alive for two days).
#
# bench_reap walks our descendants, so it reaches them without touching the
# caller or the other half of a pipeline. See common.sh.
. "$(dirname "$0")/common.sh"
trap bench_reap EXIT INT TERM

B="http://127.0.0.1:8082/brisk"
TOK=$(curl -sS -m 10 "$B/index_wr.php?mesg=getchallenge&cli_name=load303" | cut -d"|" -f2)
MP=$(printf "%s" "load303" | md5sum | cut -d" " -f1)
PRIV=$(printf "%s%s" "$TOK" "$MP" | md5sum | cut -d" " -f1)
S=$(curl -sS -m 20 "$B/index.php?name=load303&pass_private=$PRIV" | grep -oE "sess = \"[0-9a-f]+\"" | head -1 | sed "s/.*\"\(.*\)\"/\1/")
( curl -sS -N -m 60 -b "sess=$S" "$B/index_rd.php?stat=&subst=&step=-1&from=index_php&transp=xhr" > "$BRISK_WORK/ramp.stream" 2>/dev/null ) &
sleep 3
timeout 100 python3 "$BENCH/brisk_load.py" --auth --clients 150 --base 101 \
        --silent --warmup 5 --duration 35 --port 8082 > "$BRISK_WORK/load5.log" 2>&1
wait
