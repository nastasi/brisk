#!/bin/bash
URL=http://dodo.birds.lan/brisk/index.php
N=10000

# TODO parameters

declare -a cpid

for i in $(seq 1 $N); do
    curl -s -o /tmp/out.$i.$$.txt "$URL" &
    cpid[$i]=$!
done

wait ${pi[*]}
ret=$?

for i in $(seq 1 $N); do
#    ls -l /tmp/out.$i.$$.txt
    rm -f /tmp/out.$i.$$.txt
done

exit $ret