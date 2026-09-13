#!/bin/bash
# Five automatic players come in, sit down and form the table.
# Then they stay connected: the hand is played by hand.sh.
#
# usage: game.sh [table] [port]
#
# It lives in /root/load and not in /tmp, which is tmpfs: a restart of the
# container used to wipe the scripts.

# Subprocess loops outlive their parent: without this they are left orphaned,
# spinning for nothing. kill 0 kills the group, which setsid makes exclusive.
trap "kill 0" EXIT INT TERM

TAB="${1:-4}"; PORTA="${2:-8444}"
B="https://127.0.0.1:${PORTA}/brisk"
CURL="curl -sSk"
cd /root/load
rm -f auth.txt r?.stream k?.stream mani.txt tok.txt

echo "== 1. login of five authenticated users =="
for pair in "uno one" "due two" "tre thr" "qua for" "cin fiv"; do
    set -- $pair; U=$1; P=$2
    TOK=$($CURL -m 15 "$B/index_wr.php?mesg=getchallenge&cli_name=$U" | cut -d'|' -f2)
    MP=$(printf '%s' "$P" | md5sum | cut -d' ' -f1)
    PRIV=$(printf '%s%s' "$TOK" "$MP" | md5sum | cut -d' ' -f1)
    S=$($CURL -m 20 "$B/index.php?name=$U&pass_private=$PRIV" \
        | grep -oE 'sess = "[0-9a-f]+"' | head -1 | sed 's/.*"\([0-9a-f]*\)".*/\1/')
    echo "$U $S" >> auth.txt
    printf "   %-4s %s\n" "$U" "${S:-FALLITO}"
done

echo "== 2. room, and sitting down at table $TAB =="
i=0
while read U S; do
    i=$((i+1))
    ( while true; do
        $CURL -N -m 90 -b "sess=$S" \
          "$B/index_rd.php?stat=&subst=&step=-1&from=index_php&transp=xhr" >> r$i.stream 2>/dev/null
        sleep 0.3
      done ) &
    sleep 1
done < auth.txt
sleep 3
while read U S; do
    $CURL -m 20 -o /dev/null -b "sess=$S" "$B/index_wr.php?sess=$S&stp=0&mesg=sitdown%7C$TAB"
    sleep 1
done < auth.txt
sleep 4

TK=$(grep -ohE 'createCookie\("table_token", "[0-9a-f]+"' r?.stream | head -1 | grep -oE '[0-9a-f]{10,}')
echo "   table_token: ${TK:-TABLE NOT FORMED}"
echo "$TK" > tok.txt
[ -z "$TK" ] && exit 1

echo "== 3. game table =="
i=0
while read U S; do
    i=$((i+1))
    $CURL -m 20 -o /dev/null -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" \
      "$B/briskin5/index.php"
    ( while true; do
        $CURL -N -m 100 -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" \
          "$B/briskin5/index_rd.php?stat=&subst=&step=-1&from=table_php&transp=xhr" >> k$i.stream 2>/dev/null
        sleep 0.3
      done ) &
    sleep 1
done < auth.txt
sleep 6

for i in 1 2 3 4 5; do
    U=$(sed -n "${i}p" auth.txt | awk '{print $1}')
    P=$(grep -oE "card_send\([0-9]+,[0-9]+,[0-9]+" k$i.stream | head -1 | sed 's/card_send(\([0-9]*\),.*/\1/')
    H=$(grep -oE "card_send\($P,[0-9]+,[0-9]+" k$i.stream | sed 's/.*,//' | sort -n -u | tr '\n' ' ')
    echo "$i $U $P $H" >> mani.txt
    printf "   %-4s pos %s (%d cards)\n" "$U" "$P" "$(echo $H | wc -w)"
done
echo "   ready: now hand.sh plays the hand"
wait
