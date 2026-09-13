#!/bin/sh -e
### BEGIN INIT INFO
# Provides:          brisk
# Required-Start:       $local_fs $remote_fs $network $time
# Required-Stop:        $local_fs $remote_fs $network $time
# Default-Start:        2 3 4 5
# Default-Stop:         0 1 6
# Short-Description: manage brisk daemon
### END INIT INFO

BPATH="xx/home/nastasi/web/brisk"
PPATH="xx/home/nastasi/brisk-priv"
# screen suffix
SSUFF="xxbrisk"
BUSER="xxwww-data"
# NOTE: the two "su" below pass "-s /bin/bash". The system user that runs the
# daemon usually has /usr/sbin/nologin as its shell (that is the case for
# www-data on debian), and without this "su - www-data" answers "This account
# is currently not available" and the daemon does not start.
# seconds to wait exit of the process
WAITLOOP_MAX=5

#
#  MAIN
#
NL="
"
TB="	"
# scr_old="$(screen -ls | sed "s/^[ ${TB}]*//g;s/[ ${TB}]\+/ /g" | cut -d ' ' -f 1 | grep "\.${SSUFF}$")"
# echo "[$scr_old]"

case "$1" in
    stop)
        #
        #  if .pid file exists try to shutdown the process
        if [ -f "${PPATH}/brisk.pid" ]; then
            killed=0
            pid_old="$(cat "${PPATH}/brisk.pid")"
            sig="TERM"
            for i in $(seq 1 $WAITLOOP_MAX); do
                sleep 1
                if ! kill -$sig $pid_old 2>/dev/null ; then
                    killed=1
                    break
                fi
                sig=0
            done
            if [ $killed -eq 0 ]; then
                kill -KILL $pid_old 2>/dev/null || true
            fi
        fi
        #
        #  Then close our screen sessions. Each one carries the loop that
        #  respawns the daemon, so killing the process alone is not enough;
        #  and a session left behind by a start that was refused, because the
        #  daemon was already running, would wait there and take the daemon
        #  over at the next stop. That is how the instances used to pile up.
        su -s /bin/bash - ${BUSER} -c "screen -ls" 2>/dev/null \
          | sed -n "s/^[[:space:]]*\([0-9][0-9]*\.${SSUFF}\)[[:space:]].*/\1/p" \
          | while read scr ; do
                su -s /bin/bash - ${BUSER} -c "screen -S $scr -X quit" >/dev/null 2>&1 || true
            done
        su -s /bin/bash - ${BUSER} -c "screen -wipe" >/dev/null 2>&1 || true
        ;;

    devstart)
        su -s /bin/bash - ${BUSER} -c 'cd '"$BPATH"'/spush ; ./brisk-spush.php'
        ;;

    start)
        su -s /bin/bash - ${BUSER} -c 'cd '"$BPATH"'/spush ; screen -d -m -S '"${SSUFF}"' bash -c '"'"'while [ 1 ]; do cd . ; ./brisk-spush.php | grep "IN LOOP" ; if [ $? -eq 0 ]; then break ; fi ; sleep 1 ; done'"'"
        ;;
    restart)
        $0 stop
        sleep 3
        $0 start
        ;;
    *)
        echo "Usage: $0 {start|stop|restart}" >&2
        exit 1
        ;;
esac
