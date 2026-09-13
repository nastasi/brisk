#!/bin/bash
# Drives the four bots while the fifth seat is taken by a person.
#
# The bots never call: they pass the auction and leave the call to the human.
# When it is their turn they first try to pass; if after a second the turn is
# still theirs it means the auction is over, and then they play the first card
# the server accepts. That way there is no need to know which phase we are in.
B="https://127.0.0.1:8444/brisk"; TAB="${1:-4}"; cd /tmp
CURL="curl -sSk"
MAXWAIT="${2:-900}"

echo "waiting for the table to form..."
for n in $(seq 1 $MAXWAIT); do
    [ -s tok.txt ] && [ -s mani.txt ] && break
    sleep 1
done
TK=$(cat tok.txt 2>/dev/null)
if [ -z "$TK" ]; then echo "table not formed, leaving"; exit 1; fi
echo "table $TAB, token $TK"
cat mani.txt

send() { local T=$1 M=$2
    local S=$(sed -n "${T}p" auth.txt | awk '{print $2}')
    $CURL -m 15 -o /dev/null -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" \
      "$B/briskin5/index_wr.php?sess=$S&stp=0&mesg=$(printf '%s' "$M" | sed 's/|/%7C/g')"
}

# indice del bot a cui tocca, 0 se tocca all'umano o a nessuno
turn() {
    for i in 1 2 3 4; do
        [ "$(grep -oE 'remark_(on|off)' k$i.stream 2>/dev/null | tail -1)" = "remark_on" ] && { echo $i; return; }
    done
    echo 0
}
# an observer other than the player whose turn it is
obs() { [ "$1" = "1" ] && echo 2 || echo 1; }

declare -A HAND
for i in 1 2 3 4; do
    HAND[$i]=$(awk -v n=$i 'NR==n {for(j=4;j<=NF;j++) printf "%s ", $j}' mani.txt)
done

played=0; idle=0
echo "=== playing: the bots pass the auction, the call is up to the human ==="
while [ $played -lt 40 ] && [ $idle -lt 300 ]; do
    T=$(turn)
    if [ "$T" = "0" ]; then
        idle=$((idle + 1)); sleep 1; continue
    fi
    idle=0
    U=$(sed -n "${T}p" auth.txt | awk '{print $1}')

    # first guess: we are in the auction, the bot passes
    SZ=$(stat -c%s k$T.stream)
    send $T "asta|-1|0"
    sleep 1
    if [ "$(turn)" != "$T" ]; then
        echo "   $U passes"
        continue
    fi

    # the turn did not move: we are playing
    O=$(obs $T); OK=0
    for C in ${HAND[$T]}; do
        SZ=$(stat -c%s k$O.stream)
        send $T "play|$C|$((330 + RANDOM % 90))|$((260 + RANDOM % 60))"
        sleep 1
        if tail -c +$((SZ+1)) k$O.stream | grep -q "card_play("; then
            HAND[$T]=$(echo ${HAND[$T]} | tr ' ' '\n' | grep -v "^$C$" | tr '\n' ' ')
            played=$((played+1)); OK=1
            printf "   %2d. %-8s plays %2d\n" $played "$U" "$C"
            break
        fi
    done
    [ $OK -eq 0 ] && { echo "   !! $U cannot play, waiting"; sleep 2; }
done
echo "=== cards played by bots and human: $played (out of 40) ==="
