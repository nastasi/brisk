#!/bin/bash
# set -x
declare -a coor pids
#coor[0]="0,0,0,560,650"
#coor[1]="0,443,108,560,650"
#coor[2]="0,878,197,560,650"
coor[0]="0,0,0,840,975"
coor[1]="0,863,0,840,975"
coor[2]="0,1712,0,840,975"
coor[3]="0,863,300,840,975"
coor[4]="0,1712,300,840,975"


# TODO: pids2wids function IN: pids OUT: associated windows ids

ffox () {
    if [ "$1" = "-v" ]; then
        ps ax | egrep '[0-9] /usr/lib/firefox/firefox -no-remote -P (one|two|three|four|five) ' | grep -v grep | sed 's/^ *//g;s/ .*-P//g;s/ http.*//g'
    else
        ps ax | egrep '[0-9] /usr/lib/firefox/firefox -no-remote -P (one|two|three|four|five) ' | grep -v grep | sed 's/^ *//g;s/ .*//g'
    fi
}

rearrange_windows () {
    ct=0
    while true; do
        sleep 1
        wids="$(wmctrl -l -p | sed 's/ \+/|/g' | cut -d '|' -f 1,3 | egrep "\|(${pids[0]}|${pids[1]}|${pids[2]}|${pids[3]}|${pids[4]})$")"
        l="$(echo "$wids" | wc -l)"
        if [ $l -eq 5 ]; then
            for i in $(seq 0 4); do
                wid="$(echo "$wids" | grep "|${pids[$i]}\$" | cut -d '|' -f 1)"
                wmctrl -i -r $wid -e ${coor[$i]}
                echo wmctrl out: $?
            done
            break
        fi
        ct=$((ct + 1))
        if [ $ct -gt 10 ]; then
            break
        fi
    done
}

#
#  MAIN
#
HOMEPAGE="http://dodo.birds.van/brisk/index.php"
if [ "$1" = "help" -o "$1" = "-h" -o "$1" = "--help" ]; then
    echo "$0       - run firefoxes"
    echo "$0 list [-v] - list firefoxes"
    echo "$0 <stop|cont|term|kill> - send signal to firefoxes"
    echo "$0 help  - this help"
elif [ "$1" = "list" ]; then
    ffox $2
elif [ "$1" = "stop" -o "$1" = "cont" -o "$1" = "term" -o "$1" = "kill" ]; then
    case "$1" in
	stop) sig=-STOP ;;
	cont) sig=-CONT ;;
	term) sig=-TERM ;;
	kill) sig=-KILL ;;
    esac
    
    kill $sig $(ffox)
elif [ "$1" = "rearrange" ]; then
    list="$($0 list -v)"
    declare -a pids
    ct=0
    for i in one two three four five; do
        pids[$ct]="$(echo "$list" | grep -- " $i$" | sed 's/ .*//g')"
        ct=$((ct + 1))
    done
    rearrange_windows
elif [ $# -eq 0 ]; then
    ct=0
    for i in one two three four five; do
        firefox -no-remote -P $i "$HOMEPAGE?whoami=$i" &
        pd="$!"
        echo "$i: $pd"
        pids[$ct]="$pd"
        ct=$((ct + 1))
    done

    rearrange_windows
fi
