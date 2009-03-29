#!/bin/bash
src="$1"
IFS="
"
for i in `cat $src`; do
    dt="$(echo $i | cut -d '|' -f 1)"
    user="$(echo $i | cut -d '|' -f 2)"
    subj="$(echo $i | cut -d '|' -f 3)"
    mesg="$(echo $i | cut -d '|' -f 4)"

    echo "vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv"
    echo "Date :" | tr -d '\n'
    date -d @$dt
    echo "User: $user"
    echo "Subject: $subj"
    echo -e "$mesg"
    echo "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^"
done