#!/bin/bash
glob="$(grep  '$G_'  $(find -type f -name '*.ph*') | sed 's/\$G_/\n\$G_/g' | grep '^\$G_' | sed 's/[^\$a-zA-Z0-9_].*//g'  | sort | uniq | sed 's/\$//g' )"

li=""
for i in $glob ; do
   if [ $(echo "${li}, '$i'," | wc -c) -gt 80 ]; then
       echo $li
       li=""
   fi
   if [ "$li" = "" ]; then
       li="'${i}', "
   else
       li="${li} '${i}',"
   fi
done


