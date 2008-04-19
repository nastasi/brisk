#!/bin/bash
#
# Defaults
#
n_players=3
brisk_debug="0xffff"
web_path="$HOME/brisk"
legal_path="$HOME/brisk-priv"
ftok_path="$HOME/brisk-priv/ftok"
proxy_path="$HOME/brisk-priv/proxy"
web_only="FALSE"
brisk_conf="brisk.conf.pho"

if [ -f $HOME/.spawn_install ]; then
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
    echo "  -C config filename              - def. \"$brisk_conf\""
    
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

function searchetc() {
    local dstart dname pp
    dstart="$1"
    dname="$2"

    pp="$dstart"
    while [ "$pp" != "/" ]; do
        if [ -d "$pp/$dname" ]; then
            echo "$pp/$dname"
            return 0
        fi
        pp="`dirname "$pp"`"
    done
    
    return 1
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
	-C*) brisk_conf="`get_param "-C" "$1" "$2"`"; sh=$?;;
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
#  Pre-check
#
# check for etc path existence
dsta="`dirname "$web_path"`"
etc_path="`searchetc "$dsta" Etc`"
if [ $? -ne 0 ]; then
    echo "Etc directory not found"
    exit 1
fi

#
#  Installation
#
ftokk_path="${ftok_path}k"

if [ $n_players -ne 3 -a $n_players -ne 5 ]; then
    echo "n_players ($n_players) out of range (3|5)"
    exit 1
fi
if [ "$web_only" = "FALSE" ]; then
    if [ ! -d "$ftok_path" -a ! -d "$ftokk_path" ]; then
	echo "ftok_path (\"$ftok_path\") not exists"
	exit 2
    fi
    if [ -d "$ftok_path" -a -d "$ftokk_path" ]; then
        echo "ftok_path (\"$ftok_path\") and ftokk_path (\"$ftokk_path\") exist, cannot continue"
	exit 4
    fi
    if [ -d "$ftok_path" ]; then
        mv "$ftok_path" "$ftokk_path"
    fi
    touch $ftokk_path/spy.txt >/dev/null 2>&1
    if [ $? -ne 0 ]; then
	echo "ftokk_path (\"$ftokk_path\") write not allowed."
	exit 3
    fi
    rm $ftokk_path/spy.txt
    
    # create the fs subtree to enable ftok-ing
    touch ${ftokk_path}/main
    chmod 666 ${ftokk_path}/main
    for i in `seq 0 99`; do 
        touch ${ftokk_path}/table$i
        chmod 666 ${ftokk_path}/table$i
    done
fi
install -d ${web_path}__
for i in `find web -type d | grep -v /CVS | sed 's/^....//g'`; do
    install -d ${web_path}__/$i 
done

for i in `find web -name '*.php' -o -name '*.phh' -o -name '*.pho' -o -name '*.css' -o -name '*.js' -o -name '*.mp3' -o -name '*.swf' | grep -v /CVS | sed 's/^....//g'`; do
    install -m 644 web/$i ${web_path}__/$i
done

cd web
find . -name '.htaccess' -exec install -m 644 {} ${web_path}__/{} \;
cd - >/dev/null 2>&1

if [ $n_players -eq 5 ]; then
   send_time=250
else
   send_time=10
fi

# .js substitutions
sed -i "s/PLAYERS_N *= *[0-9]\+/PLAYERS_N = $n_players/g" `find ${web_path}__ -type f -name '*.js' -exec grep -l 'PLAYERS_N *= *[0-9]\+' {} \;`

sed -i "s/^var G_send_time *= *[0-9]\+/var G_send_time = $send_time/g" `find ${web_path}__ -type f -name '*.js' -exec grep -l '^var G_send_time *= *[0-9]\+' {} \;`

# .ph[pho] substitutions
sed -i "s/define *( *PLAYERS_N, *[0-9]\+ *)/define(PLAYERS_N, $n_players)/g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *PLAYERS_N, *[0-9]\+ *)' {} \;`

sed -i "s/define *( *BRISKIN5_PLAYERS_N, *[0-9]\+ *)/define(BRISKIN5_PLAYERS_N, $n_players)/g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *BRISKIN5_PLAYERS_N, *[0-9]\+ *)' {} \;`

sed -i "s@define *( *FTOK_PATH,[^)]*)@define(FTOK_PATH, \"$ftok_path\")@g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *FTOK_PATH,[^)]*)' {} \;`

sed -i "s@define *( *BRISK_DEBUG,[^)]*)@define(BRISK_DEBUG, $brisk_debug)@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *LEGAL_PATH,[^)]*)@define(LEGAL_PATH, \"$legal_path\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *PROXY_PATH,[^)]*)@define(PROXY_PATH, \"$proxy_path\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *BRISK_CONF,[^)]*)@define(BRISK_CONF, \"$brisk_conf\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@var \+xhr_rd_cookiepath \+= \+\"[^\"]*\";@var xhr_rd_cookiepath = \"$cookie_path\";@g" ${web_path}__/xhr.js
sed -i "s@var \+cookiepath \+= \+\"[^\"]*\";@var cookiepath = \"$cookie_path\";@g" ${web_path}__/commons.js

if [ -d ${web_path} ]; then
    mv ${web_path} ${web_path}.old
fi

if [ -d ../brisk-img ]; then
    cd ../brisk-img
    ./INSTALL.sh -w ${web_path}__
    cd - >/dev/null 2>&1
fi

# config file installation or diff
if [ -f "$etc_path/$brisk_conf" ]; then
    echo "Config file $etc_path/$brisk_conf exists."
    echo "=== Dump the diff. ==="
    diff -u "$etc_path/$brisk_conf" "${web_path}__""/Obj/brisk.conf-templ.pho"
    echo "===   End dump.    ==="
else
    echo "Config file $etc_path/$brisk_conf not exists."
    echo "Install a template."
    cp  "${web_path}__""/Obj/brisk.conf-templ.pho" "$etc_path/$brisk_conf"
fi

mv ${web_path}__ ${web_path}
if [ -d ${web_path}.old ]; then
    rm -rf ${web_path}.old
fi
if [ "$web_only" = "FALSE" ]; then
    mv "$ftokk_path" "$ftok_path"
fi
exit 0
