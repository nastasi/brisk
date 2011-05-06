grep UNLOCK brisk.log | sed 's/\([0-9]*\)\]\] \[\]$/\1/g;s/.*\[//g'  | sort -n
