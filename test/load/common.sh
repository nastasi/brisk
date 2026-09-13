# Shared settings of the load bench. Sourced by the scripts here, never run.
#
# The bench used to live in /root/load, with those paths written into it, and
# to run as root. It runs from the repository now, as an unprivileged user, so
# every script finds its own directory instead and keeps its working files out
# of it: auth.txt, the captured streams and the logs would otherwise be left
# lying in the git tree.

BENCH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Working files go here, and not in /tmp: that is a tmpfs, and a restart of the
# container wipes it in the middle of a session, scripts included - which is
# why they had been moved to /root/load in the first place. /var/tmp is on the
# root filesystem.
BRISK_WORK="${BRISK_WORK:-/var/tmp/brisk-load}"
mkdir -p "$BRISK_WORK"

# Where the daemon keeps its log and its pid file: the path handed to
# INSTALL.sh with -l. Getting it wrong is not harmless - the init script looks
# for brisk.pid in there, and while it cannot find it "stop" does nothing and
# every restart leaves an orphaned daemon behind instead of replacing it.
BRISK_LEGAL="${BRISK_LEGAL:-/home/brisk/legal}"

# The daemon runs as another user: restarting the service and reading its open
# descriptors need root, and the bench is unprivileged now.
SUDO="${SUDO:-sudo}"

# Kills the background loops this script started, and nothing else.
#
# The bench used to do: trap "kill 0" EXIT INT TERM. kill 0 signals the whole
# process group, which is nearly always wider than this script. It killed the
# script itself on the way out, so the exit status was always a death by
# signal and buffered output could be lost; and it reached whatever else
# shared the group - whoever launched us, or the other half of a pipeline,
# so that "brisk_step.sh | tail" printed nothing at all. Running under
# "incus exec" gave every script a session of its own and hid all of it.
#
# Walking our own descendants reaches exactly what kill 0 was there for - the
# loops inside "( ... ) &" and the curls underneath them, which inherit us as
# their parent - and nothing that we did not start ourselves.
bench_descendants() {
    local kid
    for kid in $(pgrep -P "$1" 2>/dev/null); do
        bench_descendants "$kid"
        echo "$kid"
    done
}

bench_reap() {
    trap - EXIT INT TERM

    local kids
    kids=$(bench_descendants $$)
    # drop them from the job table first, or the shell announces every kill
    disown -a 2>/dev/null || true
    if [ -n "$kids" ]; then
        kill $kids 2>/dev/null
        sleep 0.3
        kids=$(bench_descendants $$)
        [ -n "$kids" ] && kill -9 $kids 2>/dev/null
    fi
    return 0
}
