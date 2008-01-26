#!/bin/bash
#
# Defaults
#
n_players=3
brisk_debug="TRUE"
ftok_path="/var/lib/brisk"
web_path="$HOME/brisk"
web_only="FALSE"

if [ -f $HOME/.brisk_install ]; then
   . $HOME/.spawn_install
fi
if [ "x$cookie_path" = "x" ]; then
   cookie_path=$web_path
fi
function usage () {
    echo
    echo "$1 -h"
    echo "$1 [-W] [-n 3|5] [-f conffile] [-p outconf] [-d TRUE|FALSE] [-w web_dir] [-k <ftok_dir>] [-l <legal_path>] [-y <proxy_path>] [-c <cookie_path>]"
    echo "  -h this help"
    echo "  -f use this config file"
    echo "  -p save preferences in the file"
    echo "  -W web files only"
    echo "  -n number of players            - def. $n_players"
    echo "  -d activate dabug               - def. $brisk_debug"
    echo "  -w dir where place the web tree - def. \"$web_path\""
    echo "  -k dir where place ftok files   - def. \"$ftok_path\""
    echo "  -l dir where save logs          - def. \"$legal_path\""
    echo "  -y dir where place proxy files  - def. \"$proxy_path\""
    echo "  -c cookie path                  - def. \"$cookie_path\""
    
    echo
}

function get_param () {
    echo "X$2" | grep -q "^X$1\$"
    if [ $? -eq 0 ]; then
	# echo "DECHE" >&2
        echo "$3"
	return 2
    else
	# echo "DELA" >&2
        echo "$2" | cut -c 3-
        return 1
    fi
    return 0
}

#
#  MAIN
#
while [ $# -gt 0 ]; do
    # echo aa $1 xx $2 bb
    conffile=""
    case $1 in
	-f*) conffile="`get_param "-f" "$1" "$2"`"; sh=$?;;
	-p*) outconf="`get_param "-p" "$1" "$2"`"; sh=$?;;
	-n*) n_players="`get_param "-n" "$1" "$2"`"; sh=$?;;
	-d*) brisk_debug="`get_param "-d" "$1" "$2"`"; sh=$?;;
	-w*) web_path="`get_param "-w" "$1" "$2"`"; sh=$?;;
	-k*) ftok_path="`get_param "-k" "$1" "$2"`"; sh=$?;;
	-y*) proxy_path="`get_param "-y" "$1" "$2"`"; sh=$?;;
	-c*) cookie_path="`get_param "-c" "$1" "$2"`"; sh=$?;;
	-l*) legal_path="`get_param "-l" "$1" "$2"`"; sh=$?;;
	-W) web_only="TRUE";;
	-h) usage $0; exit 0;;
	*) usage $0; exit 1;;
    esac
    if [ ! -z "$conffile" ]; then
        if [ ! -f "$conffile" ]; then
            echo "config file [$conffile] not found"
   	    exit 1
        fi
        . "$conffile"
    fi
    shift $sh
done

#
#  Show parameters
#
echo "    outconf:    \"$outconf\""
echo "    n_players:   $n_players"
echo "    brisk_debug:\"$brisk_debug\""
echo "    web_path:   \"$web_path\""
echo "    ftok_path:  \"$ftok_path\""
echo "    legal_path: \"$legal_path\""
echo "    proxy_path: \"$proxy_path\""
echo "    cookie_path:\"$cookie_path\""
echo "    web_only:   \"$web_only\""

if [ ! -z "$outconf" ]; then
  ( 
    echo "#"
    echo "#  Produced automatically by brisk::INSTALL.sh"
    echo "#"
    echo "n_players=$n_players"
    echo "brisk_debug=\"$brisk_debug\""
    echo "web_path=\"$web_path\""
    echo "ftok_path=\"$ftok_path\""
    echo "proxy_path=\"$proxy_path\""
    echo "legal_path=\"$legal_path\""
    echo "cookie_path=\"$cookie_path\""
    echo "web_only=\"$web_only\""
  ) > "$outconf"
fi
#
#  Installation
#
if [ $n_players -ne 3 -a $n_players -ne 5 ]; then
    echo "n_players ($n_players) out of range (3|5)"
    exit 1
fi
if [ "$web_only" = "FALSE" ]; then
    if [ ! -d $ftok_path ]; then
	echo "ftok_path (\"$ftok_path\") not exists"
	exit 2
    fi
    touch $ftok_path/spy.txt >/dev/null 2>&1
    if [ $? -ne 0 ]; then
	echo "ftok_path (\"$ftok_path\") write not allow."
	exit 3
    fi
    rm $ftok_path/spy.txt
    
    # create the fs subtree to enable ftok-ing
    touch ${ftok_path}/main
    chmod 666 ${ftok_path}/main
fi
install -d $web_path
for i in `find web -type d | grep -v /CVS | sed 's/^....//g'`; do
    install -d ${web_path}/$i 
done

for i in `find web -name '*.php' -o -name '*.phh' -o -name '*.css' -o -name '*.js' -o -name '*.mp3' -o -name '*.swf' | grep -v /CVS | sed 's/^....//g'`; do
    install -m 644 web/$i ${web_path}/$i
done

cd web
find . -name '.htaccess' -exec install -m 644 {} ${web_path}/{} \;
cd -

if [ $n_players -eq 5 ]; then
   send_time=250
else
   send_time=10
fi

# .js substitutions
sed -i "s/PLAYERS_N *= *[0-9]\+/PLAYERS_N = $n_players/g" `find ${web_path} -type f -name '*.js' -exec grep -l 'PLAYERS_N *= *[0-9]\+' {} \;`

sed -i "s/^var G_send_time *= *[0-9]\+/var G_send_time = $send_time/g" `find ${web_path} -type f -name '*.js' -exec grep -l '^var G_send_time *= *[0-9]\+' {} \;`

# .ph[ph] substitutions
sed -i "s/define *( *PLAYERS_N, *[0-9]\+ *)/define(PLAYERS_N, $n_players)/g" `find ${web_path} -type f -name '*.ph*' -exec grep -l 'define *( *PLAYERS_N, *[0-9]\+ *)' {} \;`

sed -i "s@define *( *FTOK_PATH,[^)]*)@define(FTOK_PATH, \"$ftok_path\")@g" `find ${web_path} -type f -name '*.ph*' -exec grep -l 'define *( *FTOK_PATH,[^)]*)' {} \;`

sed -i "s@define *( *BRISK_DEBUG,[^)]*)@define(BRISK_DEBUG, $brisk_debug)@g" ${web_path}/Obj/brisk.phh

sed -i "s@define *( *LEGAL_PATH,[^)]*)@define(LEGAL_PATH, \"$legal_path\")@g" ${web_path}/Obj/brisk.phh

sed -i "s@define *( *PROXY_PATH,[^)]*)@define(PROXY_PATH, \"$proxy_path\")@g" ${web_path}/Obj/brisk.phh

sed -i "s@var \+xhr_rd_cookiepath \+= \+\"[^\"]*\";@var xhr_rd_cookiepath = \"$cookie_path\";@g" ${web_path}/xhr.js
sed -i "s@var \+cookiepath \+= \+\"[^\"]*\";@var cookiepath = \"$cookie_path\";@g" ${web_path}/commons.js

exit 0
