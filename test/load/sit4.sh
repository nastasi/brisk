#!/bin/bash
# Four automatic players sit at the table and wait for the fifth: a human
# being with a real browser. They stay connected and keep their streams open,
# so the table forms as soon as the human sits down.
# Subprocess loops outlive their parent: without this they are left orphaned,
# spinning for nothing (it happened: eighty loops alive for two days). kill 0
# kills the process group, which setsid makes exclusive to this script.
trap "kill 0" EXIT INT TERM

B="https://127.0.0.1:8444/brisk"; TAB="${1:-4}"; cd /tmp
CURL="curl -sSk"
rm -f auth.txt r?.stream k?.stream mani.txt tok.txt

for u in load001 load002 load003 load004; do
  TOK=$($CURL -m 15 "$B/index_wr.php?mesg=getchallenge&cli_name=$u" | cut -d'|' -f2)
  MP=$(printf '%s' "$u" | md5sum | cut -d' ' -f1)
  PRIV=$(printf '%s%s' "$TOK" "$MP" | md5sum | cut -d' ' -f1)
  S=$($CURL -m 20 "$B/index.php?name=$u&pass_private=$PRIV" | grep -oE 'sess = "[0-9a-f]+"' | head -1 | sed 's/.*"\([0-9a-f]*\)".*/\1/')
  echo "$u $S" >> auth.txt
  printf "   %s %s\n" "$u" "${S:-FAILED}"
done

i=0; while read U S; do i=$((i+1))
  ( while true; do $CURL -N -m 90 -b "sess=$S" \
      "$B/index_rd.php?stat=&subst=&step=-1&from=index_php&transp=xhr" >> r$i.stream 2>/dev/null
    sleep 0.3; done ) &
  sleep 0.5; done < auth.txt
sleep 3

while read U S; do
  $CURL -m 20 -o /dev/null -b "sess=$S" "$B/index_wr.php?sess=$S&stp=0&mesg=sitdown%7C$TAB"
  sleep 1
done < auth.txt
echo "   four seated at table $TAB, waiting for the fifth"

for n in $(seq 1 900); do
  TK=$(grep -ohE 'createCookie\("table_token", "[0-9a-f]+"' r?.stream | head -1 | grep -oE '[0-9a-f]{10,}')
  [ -n "$TK" ] && break
  sleep 1
done
if [ -z "$TK" ]; then echo "   table not formed within 15 minutes"; exit 1; fi
echo "$TK" > tok.txt
echo "   table formed: $TK"

i=0; while read U S; do i=$((i+1))
  $CURL -m 20 -o /dev/null -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" "$B/briskin5/index.php"
  ( while true; do $CURL -N -m 100 -b "sess=$S; table_idx=$TAB; table_token=$TK; lang=it" \
      "$B/briskin5/index_rd.php?stat=&subst=&step=-1&from=table_php&transp=xhr" >> k$i.stream 2>/dev/null
    sleep 0.3; done ) &
  sleep 0.7; done < auth.txt
sleep 6

for i in 1 2 3 4; do
  U=$(sed -n "${i}p" auth.txt | awk '{print $1}')
  P=$(grep -oE "card_send\([0-9]+,[0-9]+,[0-9]+" k$i.stream | head -1 | sed 's/card_send(\([0-9]*\),.*/\1/')
  H=$(grep -oE "card_send\($P,[0-9]+,[0-9]+" k$i.stream | sed 's/.*,//' | sort -n -u | tr '\n' ' ')
  echo "$i $U $P $H" >> mani.txt
  printf "   %s in position %s, %d cards\n" "$U" "$P" "$(echo $H | wc -w)"
done
echo "   ready"
wait
