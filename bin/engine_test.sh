#!/bin/bash
# set -x
url="http://dodo.birds.lan/brisk/test.php"
# url="http://dodo.birds.lan/pippo.php"

rm -f engine_test.log engine_test.out engine_test.in

to=1
while [ $# -gt 0 ]; do
    case $1 in
        -w)
            USE_WGET=y
            ;;
        *)
            to_tot=$1
            to=$to_tot
            break
            ;;
    esac
    shift
done

rm -f engine_test.tmp
touch engine_test.tmp
if [ $to_tot -gt 10 ]; then
    for i in $(seq 1 $((to / 10))); do
        echo "_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef_123456789abcdef" | tr -d '\n' >> engine_test.tmp
    done
    into=$((to / 10))
    into=$((into * 10))
    to=$((to - into))
fi

for i in $(seq 1 $to); do
        echo "_123456789abcdef" | tr -d '\n' >> engine_test.tmp
done
in_md5="$(cat engine_test.tmp | tr -d '\n' | md5sum | cut -c 1-7)"
echo "data=" | tr -d '\n' > engine_test.in
cat  engine_test.tmp >>  engine_test.in

if [ "$USE_WGET" = "y" ]; then
    echo "Started wget, sent "$((to_tot * 16))" (MD5 $in_md5) ... " | tr -d '\n'
    wget --post-file=engine_test.in -q -o engine_test.log -O engine_test.out "$url"
else
    echo "Started curl, sent "$((to_tot * 16))" (MD5 $in_md5) ... " | tr -d '\n'
    curl -d @engine_test.in -o engine_test.out "$url" > engine_test.log 2>&1
fi
# rm engine_test.in
echo "returned "$(cat engine_test.out | wc -c)" (MD5 "$(cat engine_test.out | md5sum | cut -c 1-7)")."
echo
