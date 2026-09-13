#!/bin/bash
# Plays a whole hand at the table formed by game.sh: auction, call and forty
# cards.
#
# usage: hand.sh [table] [port]

TAB="${1:-4}"; PORTA="${2:-8444}"
B="https://127.0.0.1:${PORTA}/brisk"
CURL="curl -sSk"
cd /root/load
TK=$(cat tok.txt)

send() {
    local T=$1 M=$2
    local S=$(sed -n "${T}p" auth.txt | awk '{print $2}')
    $CURL -m 15 -o /dev/null -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" \
      "$B/briskin5/index_wr.php?sess=$S&stp=0&mesg=$(printf '%s' "$M" | sed 's/|/%7C/g')"
}

# index of the player whose turn it is, 0 if nobody
turn() {
    for i in 1 2 3 4 5; do
        [ "$(grep -oE 'remark_(on|off)' k$i.stream 2>/dev/null | tail -1)" = "remark_on" ] && { echo $i; return; }
    done
    echo 0
}
# an observer other than the player whose turn it is: the play is recognised
# from its stream, because the one playing gets remark_off and not card_play
obs() { [ "$1" = "1" ] && echo 2 || echo 1; }

echo "== auction =="
BID=0
for r in $(seq 1 10); do
    T=$(turn); [ "$T" = "0" ] && { sleep 2; T=$(turn); }; [ "$T" = "0" ] && break
    U=$(sed -n "${T}p" auth.txt | awk '{print $1}')
    if [ "$BID" = "0" ]; then send $T "asta|0|0"; BID=$T; echo "   $U calls"
    else send $T "asta|-1|0"; echo "   $U passes"; fi
    sleep 2
    grep -q "choose_seed" k$BID.stream && { echo "   $(sed -n "${BID}p" auth.txt | awk '{print $1}') wins"; break; }
done

HB=$(awk -v n=$BID 'NR==n {for(j=4;j<=NF;j++) printf "%s ", $j}' mani.txt)
for a in 0 10 20 30; do echo " $HB " | grep -q " $a " || { CALL=$a; break; }; done
echo "   calls card $CALL"
send $BID "choose|$CALL"
sleep 3

echo "== play =="
declare -A HAND
for i in 1 2 3 4 5; do HAND[$i]=$(awk -v n=$i 'NR==n {for(j=4;j<=NF;j++) printf "%s ", $j}' mani.txt); done
played=0
for n in $(seq 1 70); do
    [ $played -ge 40 ] && break
    T=$(turn); [ "$T" = "0" ] && { sleep 2; T=$(turn); }
    [ "$T" = "0" ] && { echo "   no active turn"; break; }
    U=$(sed -n "${T}p" auth.txt | awk '{print $1}'); O=$(obs $T); OK=0
    for C in ${HAND[$T]}; do
        SZ=$(stat -c%s k$O.stream)
        # the coordinates are those of the playing area: fixed ones would
        # land every card on the same spot
        send $T "play|$C|$((330 + RANDOM % 90))|$((260 + RANDOM % 60))"
        sleep 1
        if tail -c +$((SZ+1)) k$O.stream | grep -q "card_play("; then
            HAND[$T]=$(echo ${HAND[$T]} | tr ' ' '\n' | grep -v "^$C$" | tr '\n' ' ')
            played=$((played+1)); OK=1
            printf "   %2d. %-4s -> %2d\n" $played "$U" "$C"
            break
        fi
    done
    [ $OK -eq 0 ] && { echo "   !! $U stuck"; break; }
done
echo "   === cards played: $played ==="
