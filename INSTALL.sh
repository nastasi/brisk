#!/bin/bash
# set -x
#
# Defaults
#
players_n=3
tables_n=16
tables_auth_n=6
brisk_auth_conf="brisk_auth2.conf.pho"
brisk_debug="0xffff"
web_path="$HOME/brisk"
legal_path="$HOME/brisk-priv"
ftok_path="$HOME/brisk-priv/ftok"
proxy_path="$HOME/brisk-priv/proxy"
web_only="FALSE"
brisk_conf="brisk.conf.pho"

if [ "$1" = "chk" ]; then
    set -e
    oldifs="$IFS"
    IFS='
'
    for i in $(find -name '*.pho' -o -name '*.phh' -o -name '*.php'); do
        php5 -l $i
    done
    exit 0
fi

# before all check errors on the sources
$0 chk || exit 3

if [ "$1" = "pkg" ]; then
    if [ "$2" != "" ]; then
        tag="$2"
    else
        tag="$(git describe)"
    fi
    nam1="brisk_${tag}.tgz"
    nam2="brisk-img_${tag}.tgz"
    echo "Build packages ${nam1} and ${nam2}."
    read -p "Proceed [y/n]: " a
    if [ "$a" != "y" -a  "$a" != "Y" ]; then
        exit 1
    fi
    git archive --format=tar --prefix=brisk-${tag}/brisk/ $tag | gzip > ../$nam1
    cd ../brisk-img
    git archive --format=tar --prefix=brisk-${tag}/brisk-img/ $tag | gzip > ../$nam2
    cd -
    exit 0
fi
    
if [ -f $HOME/.brisk_install ]; then
   . $HOME/.brisk_install
fi

if [ "x$cookie_path" = "x" ]; then
   cookie_path=$web_path
fi

function usage () {
    echo
    echo "$1 -h"
    echo "$1 chk                          - run lintian on all ph* files."
    echo "$1 pkg                          - build brisk packages."
    echo "$1 [-W] [-n 3|5] [-t <(n>=4)>] [-T <auth_tab>] [-a <auth_file_name>] [-f conffile] [-p outconf] [-d TRUE|FALSE] [-w web_dir] [-k <ftok_dir>] [-l <legal_path>] [-y <proxy_path>] [-c <cookie_path>]"
    echo "  -h this help"
    echo "  -f use this config file"
    echo "  -p save preferences in the file"
    echo "  -W web files only"
    echo "  -n number of players            - def. $players_n"
    echo "  -t number of tables             - def. $tables_n"
    echo "  -T number of auth-only tables   - def. $tables_auth_n"
    echo "  -a authorization file name      - def. \"$brisk_auth_conf\""
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
	-n*) players_n="`get_param "-n" "$1" "$2"`"; sh=$?;;
	-t*) tables_n="`get_param "-t" "$1" "$2"`"; sh=$?;;
	-T*) tables_auth_n="`get_param "-T" "$1" "$2"`"; sh=$?;;
        -a*) brisk_auth_conf="`get_param "-a" "$1" "$2"`"; sh=$?;;
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
echo "    players_n:   $players_n"
echo "    tables_n:   $tables_n"
echo "    tables_auth_n: $tables_auth_n"
echo "    brisk_auth_conf: \"$brisk_auth_conf\""
echo "    brisk_debug:\"$brisk_debug\""
echo "    web_path:   \"$web_path\""
echo "    ftok_path:  \"$ftok_path\""
echo "    legal_path: \"$legal_path\""
echo "    proxy_path: \"$proxy_path\""
echo "    cookie_path:\"$cookie_path\""
echo "    brisk_conf:\"$brisk_conf\""
echo "    web_only:   \"$web_only\""

if [ ! -z "$outconf" ]; then
  ( 
    echo "#"
    echo "#  Produced automatically by brisk::INSTALL.sh"
    echo "#"
    echo "players_n=$players_n"
    echo "tables_n=$tables_n"
    echo "tables_auth_n=$tables_auth_n"
    echo "brisk_auth_conf=\"$brisk_auth_conf\""
    echo "brisk_debug=\"$brisk_debug\""
    echo "web_path=\"$web_path\""
    echo "ftok_path=\"$ftok_path\""
    echo "proxy_path=\"$proxy_path\""
    echo "legal_path=\"$legal_path\""
    echo "cookie_path=\"$cookie_path\""
    echo "brisk_conf=\"$brisk_conf\""
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

if [ $players_n -ne 3 -a $players_n -ne 5 ]; then
    echo "players_n ($players_n) out of range (3|5)"
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
    touch ${ftokk_path}/challenges
    chmod 666 ${ftokk_path}/challenges
    touch ${ftokk_path}/hardbans
    chmod 666 ${ftokk_path}/hardbans
    touch ${ftokk_path}/warrant
    chmod 666 ${ftokk_path}/warrant
    touch ${ftokk_path}/poll
    chmod 666 ${ftokk_path}/poll
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

if [ $players_n -eq 5 ]; then
   send_time=250
else
   send_time=10
fi

# .js substitutions
sed -i "s/PLAYERS_N *= *[0-9]\+/PLAYERS_N = $players_n/g" `find ${web_path}__ -type f -name '*.js' -exec grep -l 'PLAYERS_N *= *[0-9]\+' {} \;`

sed -i "s/^var G_send_time *= *[0-9]\+/var G_send_time = $send_time/g" `find ${web_path}__ -type f -name '*.js' -exec grep -l '^var G_send_time *= *[0-9]\+' {} \;`

# .ph[pho] substitutions
sed -i "s/define *( *PLAYERS_N, *[0-9]\+ *)/define(PLAYERS_N, $players_n)/g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *PLAYERS_N, *[0-9]\+ *)' {} \;`

sed -i "s/define *( *BRISKIN5_PLAYERS_N, *[0-9]\+ *)/define(BRISKIN5_PLAYERS_N, $players_n)/g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *BRISKIN5_PLAYERS_N, *[0-9]\+ *)' {} \;`

sed -i "s@define *( *FTOK_PATH,[^)]*)@define(FTOK_PATH, \"$ftok_path\")@g" `find ${web_path}__ -type f -name '*.ph*' -exec grep -l 'define *( *FTOK_PATH,[^)]*)' {} \;`

sed -i "s@define *( *TABLES_N,[^)]*)@define(TABLES_N, $tables_n)@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *TABLES_AUTH_N,[^)]*)@define(TABLES_AUTH_N, $tables_auth_n)@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *BRISK_DEBUG,[^)]*)@define(BRISK_DEBUG, $brisk_debug)@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *LEGAL_PATH,[^)]*)@define(LEGAL_PATH, \"$legal_path\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *PROXY_PATH,[^)]*)@define(PROXY_PATH, \"$proxy_path\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *BRISK_CONF,[^)]*)@define(BRISK_CONF, \"$brisk_conf\")@g" ${web_path}__/Obj/brisk.phh

sed -i "s@define *( *BRISK_AUTH_CONF,[^)]*)@define(BRISK_AUTH_CONF, \"$brisk_auth_conf\")@g" ${web_path}__/Obj/auth.phh

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
    # diff -u "$etc_path/$brisk_conf" "${web_path}__""/Obj/brisk.conf-templ.pho"
    diff -u <(cat "$etc_path/$brisk_conf" | grep '\$[a-zA-Z_ ]\+=' | sed 's/ = .*/ = /g' | sort | uniq) <(cat "${web_path}__""/Obj/brisk.conf-templ.pho" | grep '\$[a-zA-Z_ ]\+=' | sed 's/ = .*/ = /g' | sort | uniq )
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
