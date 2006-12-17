#!/bin/bash
#
# Defaults
#
n_players=3
ftok_path="/var/lib/brisk"
web_path="$HOME/brisk"
web_only=0

if [ -f $HOME/.brisk_install ]; then
   . $HOME/.brisk_install
fi

function usage () {
    echo
    echo "$1 [-n 3|5] [-w web_dir] [-k <ftok_dir>] [-W]"
    echo "  -h this help"
    echo "  -n number of players - def. $n_players"
    echo "  -w dir where place the web tree - def. \"$web_path\""
    echo "  -k dir where place ftok files   - def. \"$ftok_path\""
    echo "  -W install web files only"
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
    case $1 in
	-n*) n_players="`get_param "-n" "$1" "$2"`"; sh=$?;;
	-w*) web_path="`get_param "-w" "$1" "$2"`"; sh=$?;;
	-k*) ftok_path="`get_param "-k" "$1" "$2"`"; sh=$?;;
	-W) web_only=1;;
	-h) usage $0; exit 0;;
	*) usage $0; exit 1;;
    esac
    # echo "SH $sh"
    shift $sh
done

#
#  Show parameters
#
echo "    web_path:  \"$web_path\""
echo "    ftok_path: \"$ftok_path\""
echo "    n_players:   $n_players"

#
#  Installation
#
if [ $web_only -eq 0 ]; then
    if [ $n_players -ne 3 -a $n_players -ne 5 ]; then
	echo "n_players ($n_players) out of range (3|5)"
	exit 1
    elif [ ! -d $ftok_path ]; then
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
install -m 644 web/*.{php,phh,css,js} ${web_path}
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

sed -i "s@define *( *FTOK_PATH,[^)]*)@define( FTOK_PATH, \"$ftok_path\")@g" `find ${web_path} -type f -name '*.ph*' -exec grep -l 'define *( *FTOK_PATH,[^)]*)' {} \;`

# install -d ${web_path}/img
# install -m 644 `ls img/*.{jpg,png} | grep -v 'img/src_'` ${web_path}/img

exit 0