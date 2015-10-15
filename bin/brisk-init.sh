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
        ;;

    devstart)
        su - ${BUSER} -c 'cd '"$BPATH"'/spush ; ./brisk-spush.php'
        ;;

    start)
        su - ${BUSER} -c 'cd '"$BPATH"'/spush ; screen -d -m -S '"${SSUFF}"' bash -c '"'"'while [ 1 ]; do cd . ; ./brisk-spush.php \| grep "IN LOOP" ; if [ $? -eq 0 ]; then break ; fi ; done'"'"
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
