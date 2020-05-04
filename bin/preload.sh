#!/bin/bash
#
#  brisk - preload.sh
#
#  Copyright (C) 2011-2012 Matteo Nastasi
#                          mailto: nastasi@alternativeoutput.it
#                                  matteo.nastasi@milug.org
#                          web: http://www.alternativeoutput.it
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABLILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# General Public License for more details. You should have received a
# copy of the GNU General Public License along with this program; if
# not, write to the Free Software Foundation, Inc, 59 Temple Place -
# Suite 330, Boston, MA 02111-1307, USA.
#
#

IMGPATHBASE="../brisk-img/"

# set -x

# (
# echo '<?php'
# echo 'header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1'
# echo 'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past'
# echo '?>'
# ) > $OUTFILE

function imglist_fla () {
    local pname lang
    pname="$1"
    lang="$2"
    rex="$3"
    wrex="$4"
    ret=""
#    for i in $(find "$pname" -maxdepth 1 -type f -name '*.jpg' -o -name '*.png' -o -name '*.gif' | grep -v '/src_' | sort); do
    for i in $(find "$pname" -maxdepth 1 -type f -name 'asta*.png' -o -name 'st_*.png' | grep -v '/src_' | sort); do
        if [ "$rex" != "" ]; then
            echo "$i" | grep -q "$rex"
            rt=$?
            if [ "$wrex" = "y" -a $rt -ne 0  ]; then
                continue
            fi
            if [ "$wrex" = "n" -a $rt -eq 0  ]; then
                continue
            fi
        fi
        echo "$i" | grep -q '.*\-[a-z][a-z]\....$'
        if [ $? -eq 0 ]; then
            # se file con suffisso di lingua
            suff="$(echo "$i" | sed 's/\(.*\)\-\([a-z][a-z]\)\.\(...\)$/\2/g')"

            if [ "$lang" = "$suff" ]; then
                echo "$i"
            fi
        else
            eni="$(echo "$i" | sed 's/\(.*\)\.\(...\)$/\1-en.\2/g')"
            if [ -f $eni ]; then
                # esiste la versione _en
                if [ "$lang" = "it" ]; then
                    # se lingua italiana le img mlang nn hanno estensione quindi va presa
                    echo "$i"
                fi
            else
                # NON esiste la versione _en quindi e' una immagine NON mlang e va comunque presa
                echo "$i"
            fi
        fi
    done 
}

function imglist () {
    local abspa
    abspa="${IMGPATHBASE}${1}img"
    if [ "$1" = "" ]; then
        ls -Sd $( imglist_fla "$abspa" "$2" ) | grep -v '^\.$'
    elif [ "$1" = "briskin5/" ]; then
        # rex='/[0-9][0-9][^/]*$'
        rex=''
        ls -Sd $( imglist_fla "$abspa" "$2" "$rex" "y" ) | grep -v '^\.$'
        ls -Sd $( imglist_fla "$abspa" "$2" "$rex" "n" ) | grep -v '^\.$'
    fi
}

for lang in it en; do
    if [ "$lang" = "it" ]; then
        fsuf=""
    else
        fsuf="-${lang}"
    fi

    for dpath in "" briskin5/ ; do
        OUTFILE="web/${dpath}"preload_img${fsuf}.js
        echo "creating $OUTFILE ..."
        rm -f $OUTFILE
        IMGPATH="${IMGPATHBASE}${dpath}img"
        (
            echo "var g_preload_img_arr = new Array( "
            first=1
            spa="            "
            ltri="$(echo "$IMGPATH" | wc -c)"
            for i in $(imglist "$dpath" "$lang"); do
                if [ $first -ne 1 ]; then
                    echo -n ","
                    if [ $((ct % 2)) -eq 0 ]; then
                        echo
                        echo -n "$spa"
                    fi
                else
                    echo -n "$spa"
                fi
                outna="img/$(echo "$i" | cut -c $((ltri + 1))-)"
                if [ $((ct % 2)) -eq 0 ]; then
                    echo -n "\"$outna\""
                else
                    echo -n " \"$outna\""
                fi
                ct=$((ct + 1))
                first=0
            done
            echo "CT: $ct" >&2
            echo ");"
        ) >> $OUTFILE
        
        (
            echo "var g_preload_imgsz_arr = new Array( "
            first=1
            sum=0
            spa="            "
            tot=0
            ltri="$(echo "$IMGPATH" | wc -c)"
            for i in $(imglist "$dpath" "$lang"); do
                outna="$(echo "$i" | cut -c $((ltri + 1))-)"
                sz="$(stat -c '%s' $IMGPATH/$outna)"
                tot=$((tot + sz))
            done
            
            for i in $(imglist "$dpath" "$lang"); do
                outna="$(echo "$i" | cut -c $((ltri + 1))-)"
                if [ $first -ne 1 ]; then
                    echo -n ","
                    if [ $((ct % 8)) -eq 0 ]; then
                        echo
                        echo -n "$spa"
                    fi
                else
                    echo -n "$spa"
                fi
                sz="$(stat -c '%s' $IMGPATH/$outna)"
                sum=$((sum + sz))
                cur="$(echo "100.0 * $sum / $tot" | bc -l | sed 's/\(\.[0-9]\)[0-9]*/\1/g')"
                if [ $((ct % 8)) -eq 0 ]; then
                    echo -n "\"$cur\""
                else
                    echo -n " \"$cur\""
                fi
                ct=$((ct + 1))
                first=0
            done
            
            echo "CT2: $ct" >&2

            echo ");"
        ) >> $OUTFILE
    done
done
exit 0
