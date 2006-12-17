#!/bin/bash

OUTFILE=web/preload_img.js
IMGPATH=../brisk-img/img

# (
# echo '<?php'
# echo 'header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1'
# echo 'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past'
# echo '?>'
# ) > $OUTFILE

rm -f $OUTFILE

(
echo "var g_preload_img_arr = new Array( "
first=1
spa="            "
for i in `ls -S $IMGPATH/*.{jpg,png} | grep -v '/src_'`; do
   if [ $first -ne 1 ]; then
      echo -n ", "
      if [ $ct -eq 4 ]; then
         echo
         echo -n "$spa"
         ct=0
      fi
   else
      echo -n "$spa"
   fi
   outna="img/`basename $i`"
   echo -n "\"$outna\""
   ct=$((ct + 1))
   first=0
done

echo ");"
) >> $OUTFILE

(
echo "var g_preload_imgsz_arr = new Array( "
first=1
sum=0
spa="            "
tot=0
for i in `ls -S $IMGPATH`; do
   sz="`stat -c '%s' $IMGPATH/$i`"
   tot=$((tot + sz))
done

for i in `ls -S $IMGPATH`; do
   if [ $first -ne 1 ]; then
      echo -n ", "
      if [ $ct -eq 8 ]; then
         echo
         echo -n "$spa"
         ct=0
      fi
   else
      echo -n "$spa"
   fi
   sz="`stat -c '%s' $IMGPATH/$i`"
   sum=$((sum + sz))
   cur="`echo "100.0 * $sum / $tot" | bc -l | sed 's/\(\.[0-9]\)[0-9]*/\1/g'`"
   echo -n "\"$cur\""
   ct=$((ct + 1))
   first=0
done

echo ");"
) >> $OUTFILE

exit 0
