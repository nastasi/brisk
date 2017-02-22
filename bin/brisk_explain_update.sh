#!/bin/bash
set -x
export PATH=/home/nastasi/bin:/usr/local/bin:/usr/bin:/bin:/usr/games
B_HOSTNAME="http://localhost/brisk"
# now="$(date -d '2014-01-21 23:56:00' +%s)"
now="$(date +%s)"

to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "($now / 86400) * 86400 + 7200 " | bc))"
from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "($now / 86400) * 86400 + 7200 - (86400)" | bc))"
# to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now + 7200 " | bc))"
# from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now - 9200 " | bc))"

curl -d 'pazz=yourpasswd' "$B_HOSTNAME/briskin5/stat-day.php?from=$from&to=$to"
