#!/bin/bash
# One step of the load test: restarts the daemon so as to start from an empty
# room, launches the generator and samples the resources while it runs.
#
# usage: brisk_step.sh <clients> <duration> <write_period> [--silent] [--tls]
# Subprocess loops outlive their parent: without this they are left orphaned,
# spinning for nothing (it happened: eighty loops alive for two days). kill 0
# kills the process group, which setsid makes exclusive to this script.
trap "kill 0" EXIT INT TERM

N="${1:-50}"; DUR="${2:-120}"; PER="${3:-10}"; shift 3
EXTRA="$*"
WARM=30
PORT="${PORT:-8082}"
echo "$EXTRA" | grep -q -- "--tls" && PORT=8444

# empty room: the users of the previous step would stay connected
p=$(pgrep -f 'php \./brisk-spush\.php'); [ -n "$p" ] && kill $p; sleep 2
s=$(pgrep -u www-data -x screen); [ -n "$s" ] && kill $s 2>/dev/null; sleep 1
rm -f /tmp/brisk.log
su -s /bin/bash www-data -c "cd /home/brisk/web/brisk/spush && screen -d -m -S brisk -L -Logfile /tmp/brisk.log ./brisk-spush.php"
sleep 4

echo "=== $N clients, $DUR s, one write every $PER s $EXTRA ==="
( sleep $((WARM + 3)); /root/load/brisk_sample.sh "$DUR" 5 "$N" ) > /tmp/sample.out 2>&1 &
SAMP=$!
cd /root/load
timeout $((WARM + DUR + 120)) python3 brisk_load.py --auth --clients "$N" \
        --warmup "$WARM" --duration "$DUR" --period "$PER" --port "$PORT" $EXTRA
wait $SAMP
cat /tmp/sample.out
echo "  daemon log: $(wc -l < /tmp/brisk.log) lines, of which unexpected: $(tr -d '#\r' < /tmp/brisk.log | grep -icE 'warning|notice|error|fatal')"
echo "  nginx errors: $(grep -cE ' \[(error|crit|alert)\] ' /var/log/nginx/error.log 2>/dev/null || echo 0)"
