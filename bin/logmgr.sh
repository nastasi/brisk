#!/bin/bash

# define(DBG_ONL2, 0x0001);
# define(DBG_ONLY, 0x0002);
# define(DBG_MAIN, 0x0004);
# define(DBG_READ, 0x0008);
# define(DBG_REA2, 0x0010);
# define(DBG_SEND, 0x0020);
# define(DBG_LOCK, 0x0040);
# define(DBG_WRIT, 0x0080);
# define(DBG_LOAD, 0x0100);
# define(DBG_AUTH, 0x0200);
# define(DBG_CRIT, 0x0400);

actflg="`grep 'define(BRISK_DEBUG,' Obj/brisk.phh | sed 's/.*define(BRISK_DEBUG, *//g;s/).*//g'`"

if [ "$actflg" == "" ]; then
    echo "BRISK_DEBUG define not found"
    exit 1
fi

ct=0
for log in log_only2 log_only log_main log_rd log_rd2 log_send log_lock log_wr log_load log_auth log_crit; do
    curflg="$((1 << $ct))"
    if [ $((actflg & curflg)) -eq 0 ]; then
        echo "$log isn't active"
    fi
    ct=$((ct + 1))
done

echo 

for f in $(find | grep '\.ph[pho]'); do
    echo $f
    ct=0
    sed -i 's@; *// *LogMgr:@@g' $f
    for log in log_only2 log_only log_main log_rd log_rd2 log_send log_lock log_wr log_load log_auth log_crit; do
        curflg="$((1 << $ct))"
        if [ $((actflg & curflg)) -eq 0 ]; then
            sed -i "s@$log(\(.*;\)@; // LogMgr:$log(\1@g" $f
        fi
        ct=$((ct + 1))
    done
done

