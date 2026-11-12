#!/bin/bash
# Shuts the whole test bench down and shows the final state.
# To be run ALWAYS at the end of a test session.
#
# It does not use "pkill -f": the pattern would end up in the command line of
# this very script, which would kill itself and leave the targets alive. That
# happened several times, and it is how eighty loops survived for two days.

. "$(dirname "$0")/common.sh"

MIO=$$
PAT="game\\.sh|hand\\.sh|game_pkg|game_https|game_t5|game_t9|hand_pkg|hand_https|sit4|play_human"
PAT="$PAT|join4|gjoin|ramp_probe|brisk_load|slowprobe|wsprobe|takeover"
PAT="$PAT|ghostswap|burst"

pidlist() {
    ps ax -o pid=,args= | awk -v mio="$MIO" '$1 != mio { print }' \
        | grep -E "$PAT" | grep -v grep | awk '{ print $1 }'
}

echo "test scripts found: $(pidlist | grep -c .)"
for p in $(pidlist); do kill "$p" 2>/dev/null; done
sleep 3
for p in $(pidlist); do kill -9 "$p" 2>/dev/null; done
for p in $(pgrep -x curl); do kill -9 "$p" 2>/dev/null; done
sleep 2

# The capture files grow without bound: two days of runaway loops had filled
# the disk, which is the same one as the host's. The streams grow the whole
# time they are open, so they are the ones that hurt.
rm -f "$BRISK_WORK"/*.stream "$BRISK_WORK"/*.html "$BRISK_WORK"/*.log \
      "$BRISK_WORK"/*.out "$BRISK_WORK"/auth.txt "$BRISK_WORK"/tok.txt \
      "$BRISK_WORK"/mani.txt 2>/dev/null

d="$(pgrep -f 'php \./brisk-spush\.php' | head -1)"
echo
echo "=== final state ==="
printf "  test scripts running:   %s\n" "$(pidlist | grep -c .)"
printf "  curl running:           %s\n" "$(pgrep -x curl | wc -l)"
printf "  daemon:                 %s process, %s descriptors (10 are the listening sockets)\n" \
       "$(pgrep -f 'php \./brisk-spush\.php' | wc -l)" "$($SUDO ls /proc/$d/fd 2>/dev/null | wc -l)"
printf "  disk space:             %s\n" \
       "$(df -h / | awk 'NR==2 { print $4 " free (" $5 " used)" }')"
printf "  daemon log:             %s\n" "$(du -sh "$BRISK_LEGAL/brisk.log" 2>/dev/null | cut -f1)"
printf "  working files:          %s in %s\n" \
       "$(ls -1 "$BRISK_WORK" 2>/dev/null | wc -l)" "$BRISK_WORK"
printf "  load:                   %s\n" "$(uptime | sed 's/.*average: //')"
