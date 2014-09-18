#!/bin/bash
# set -x
cd "$PWD" 2>/dev/null
dirnm="$PWD"
cwd_old="$(ls -i -d "$dirnm" | cut -d ' ' -f 1)"
pid_exe=""
while true; do
    if [ "$pid_exe" = "" ]; then
        sleep 2
        cd "$dirnm" 2>/dev/null
        "$@" &
        pid_exe="$!"
    fi
    sleep 1
    kill -0 $pid_exe >/dev/null 2>&1
    if [ $? -ne 0 ]; then
	echo "Process not present"
	exit 0
    fi
    for tloop in 1 2 3 4 5; do
        cwd_cur="$(ls -i -d "$dirnm"  2>/dev/null | cut -d ' ' -f 1)"
        if [ "$cwd_cur" ]; then
            break
        fi
        sleep 1
    done
    if [ $tloop -eq 5 ]; then
        echo "Unavailable current working directory"
	exit 1
    fi  
    if [ "$cwd_cur" != "$cwd_old" ]; then
	kill $pid_exe
	wait $pid_exe
        echo "WAIT EXIT: $?"
        pid_exe=""
    fi
    cwd_old="$cwd_cur"
done

