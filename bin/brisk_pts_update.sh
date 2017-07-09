#!/bin/bash
set -x
B_HOSTNAME="http://localhost/brisk"
export PATH=/home/nastasi/bin:/usr/local/bin:/usr/bin:/bin:/usr/games
curl -d 'pazz=yourpasswd' "$B_HOSTNAME/briskin5/statadm.php"
