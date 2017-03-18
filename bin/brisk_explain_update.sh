#!/bin/bash
# set -x
export PATH=/home/nastasi/bin:/usr/local/bin:/usr/bin:/bin:/usr/games

CONFIG_FILE="$HOME/.brisk_install"

# default values
web_url="http://localhost/brisk"
admin_password=""
if [ -f "$CONFIG_FILE" ]; then
   source "$CONFIG_FILE"
   conffile_in="$CONFIG_FILE"
fi

tty -s
is_a_tty=$?
if [ -z "$admin_password" ]; then
    if [ "$is_a_tty" -eq 0 ]; then
        read -s -p "Please insert admin password (no echo): " admin_password
    else
        echo "Incomplete configuration"
        exit 1
    fi
fi

# now="$(date -d '2014-01-21 23:56:00' +%s)"
now="$(date +%s)"

to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "($now / 86400) * 86400 + 7200 " | bc))"
from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "($now / 86400) * 86400 + 7200 - (86400)" | bc))"
# to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now + 7200 " | bc))"
# from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now - 9200 " | bc))"

curl -d "pazz=$admin_password" "$web_url/briskin5/stat-day.php?from=$from&to=$to"
