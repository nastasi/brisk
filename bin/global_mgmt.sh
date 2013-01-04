#!/bin/bash
INFILE="web/Obj/brisk.conf-templ.pho"
LINELEN=80

#
#  MAIN
#
if [ $# -gt 0 ]; then
    INFILE="$1"
fi

list="$(cat $INFILE | grep '^[ 	]*$G_[a-zA-Z0-9_-]\+ = ' | sed 's/ = .*//g;s/^[ 	]*//g;' | sort -u)"

bf=""
sep_orig="    GLOBAL "
sep="$sep_orig"
glo="$(for i in $list; do
    bf_old="$bf"
    bf="${bf}${sep}${i}"
    bf_l="$(echo "$bf" | wc -c )"
    if [ $bf_l -gt $LINELEN ]; then
        echo "$bf_old;"
        sep="$sep_orig"
        bf="${sep}${i}"
        sep=", "
    else
        sep=", "
    fi
done ; echo "${bf};")"
echo "// ---=== GLOBALS begin ===---"
echo "$glo"
echo "// ---=== GLOBALS  end  ===---"

pri="$(for i in $list; do
    name="$(echo "$i" | cut -c 2-)"
    echo "    fprintf(STDERR, \"$name = [%s]\n\", print_r($i, TRUE));"
done)"


cat <<EOF

function global_dump()
{
$glo

$pri
}
EOF

