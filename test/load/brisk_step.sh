#!/bin/bash
# One step of the load test: restarts the daemon so as to start from an empty
# room, launches the generator and samples the resources while it runs.
#
# usage: brisk_step.sh <clients> <duration> <write_period> [--silent] [--tls]
# Subprocess loops outlive their parent: without this they are left orphaned,
# spinning for nothing (it happened: eighty loops alive for two days).
#
# bench_reap walks our descendants, so it reaches them without touching the
# caller or the other half of a pipeline. See common.sh.
. "$(dirname "$0")/common.sh"
trap bench_reap EXIT INT TERM


N="${1:-50}"; DUR="${2:-120}"; PER="${3:-10}"; shift 3
EXTRA="$*"
WARM=30
PORT="${PORT:-8082}"
echo "$EXTRA" | grep -q -- "--tls" && PORT=8444

# Empty room: the users of the previous step would stay connected.
#
# Through the init script, never by hand. Starting the daemon beside one that
# is already running is what used to leave orphaned instances behind: the
# newcomer unlinks the socket files and binds its own, taking every new
# connection, while the first one stays alive on its own shared memory and
# nothing says so. "stop" also closes the screen sessions, which carry the
# loop that would otherwise respawn what it just killed.
LOG0=$(wc -l < "$BRISK_LEGAL/brisk.log" 2>/dev/null || echo 0)
NGX0=$($SUDO grep -cE ' \[(error|crit|alert)\] ' /var/log/nginx/error.log 2>/dev/null || echo 0)
$SUDO systemctl restart brisk
sleep 4

echo "=== $N clients, $DUR s, one write every $PER s $EXTRA ==="
( sleep $((WARM + 3)); "$BENCH/brisk_sample.sh" "$DUR" 5 "$N" ) > "$BRISK_WORK/sample.out" 2>&1 &
SAMP=$!
timeout $((WARM + DUR + 120)) python3 "$BENCH/brisk_load.py" --auth --clients "$N" \
        --warmup "$WARM" --duration "$DUR" --period "$PER" --port "$PORT" $EXTRA
wait $SAMP
cat "$BRISK_WORK/sample.out"
# The log is cumulative, so what counts is what this step added to it
LOG1=$(wc -l < "$BRISK_LEGAL/brisk.log" 2>/dev/null || echo 0)
echo "  daemon log: $((LOG1 - LOG0)) new lines, of which unexpected: $(tail -n +$((LOG0 + 1)) "$BRISK_LEGAL/brisk.log" 2>/dev/null | grep -icE 'warning|notice|error|fatal|crit')"
# the nginx log is 640 www-data:adm, unreadable to the unprivileged bench
NGX1=$($SUDO grep -cE ' \[(error|crit|alert)\] ' /var/log/nginx/error.log 2>/dev/null || echo 0)
echo "  nginx errors: $((NGX1 - NGX0)) new"
