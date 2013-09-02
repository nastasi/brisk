#!/bin/bash
# set -x

#
#  functions
usage () {
    echo
    echo "USAGE:"
    echo "  $0 <conf-file>"
    echo
    exit $1
}


if [ $# -lt 1 ]; then
    usage 1
fi

finname="$1"

if [ ! -f "$finname" ]; then
    exit 2
fi
source "$finname"

for gen_id in $(seq 0 $((${#gens_prefix[@]} - 1))); do
    echo "// GENERATION ${gens_prefix[$gen_id]}" 
    names_n="$(echo "${gens_names[$gen_id]}" | sed 's/|/\n/g' | wc -l)" 
    for name_id in $(seq 0 $((names_n - 1))); do
        hue="$(echo "($name_id * 255 ) / $names_n" | bc -l)"
        col100="$(./rgb_hsv.php -toxrgb 255 $hue 255.0 255.0)"
        col33="$(./rgb_hsv.php -toxrgb 255 $hue 85.0 255.0)"
        col17="$(./rgb_hsv.php -toxrgb 255 $hue 42.0 255.0)"

        name="$(echo "${gens_names[$gen_id]}" | cut -d '|' -f $((name_id + 1)))" 
        if [ $names_n -gt 1 ]; then
            name="${gens_prefix[$gen_id]}_${name}" 
        else
            name="${gens_prefix[$gen_id]}"
        fi
        echo "// [${name}][$col100][$col33][$col17]"
cat <<EOF
  subgraph cluster_${name} {
    fontsize=18;
    label="${name}";
    fillcolor="#${col33}";
    color="#${col100}";
    style="rounded,filled";
   node [shape=record, color="#${col100}", style=filled, fillcolor="#${col17}"];
    ${name} [label="<1>aaa aaa|<2>bbb bbb|<3>ccc ccc|<4>ddd ddd|<5>eee eee"];
  }
EOF
    done
done

for gen_id in $(seq 0 $((${#gens_prefix[@]} - 1))); do
    echo "// GENERATION ${gens_prefix[$gen_id]}"
    names_n="$(echo "${gens_names[$gen_id]}" | sed 's/|/\n/g' | wc -l)" 
    for name_id in $(seq 0 $((names_n - 1))); do
        hue="$(echo "($name_id * 255 ) / $names_n" | bc -l)"
        col100="$(./rgb_hsv.php -toxrgb 255 $hue 255.0 255.0)"

        gnxt_id=$((gen_id + 1))
        if [ "${gens_prefix[$gnxt_id]}" = "" ]; then
            break
        fi
        gnxt_names_n="$(echo "${gens_names[$gnxt_id]}" | sed 's/|/\n/g' | wc -l)" 

        start=$((name_id % gnxt_names_n))
        startp1=$(( ( name_id + 1) % gnxt_names_n))

        ct=1
        col="#$col100"
        for gnxt_name_id in $start $startp1; do


            name="$(echo "${gens_names[$gen_id]}" | cut -d '|' -f $((name_id + 1)))" 
            if [ $names_n -gt 1 ]; then
                name="${gens_prefix[$gen_id]}_${name}" 
            else
                name="${gens_prefix[$gen_id]}"
            fi
            gnxt_name="$(echo "${gens_names[$gnxt_id]}" | cut -d '|' -f $((gnxt_name_id + 1)))" 
            if [ $gnxt_names_n -gt 1 ]; then
                gnxt_name="${gens_prefix[$gnxt_id]}_${gnxt_name}" 
            else
                gnxt_name="${gens_prefix[$gnxt_id]}"
            fi
            echo "${name}:${ct} -> ${gnxt_name}:2 [lhead=\"cluster_${gnxt_name}\", color=\"${col}\"];" 

            ct=$((ct + 1))
            col="black"
        done
    done
done

exit 0

