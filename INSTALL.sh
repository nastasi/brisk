#!/bin/bash
# set -x
#
# Defaults
#
CONFIG_FILE="$HOME/.brisk_install"

apache_conf="/etc/apache2/sites-available/default"
card_hand=3
players_n=3
tables_n=44
tables_appr_n=12
tables_auth_n=8
tables_cert_n=4
brisk_auth_conf="brisk_spu_auth.conf.pho"
brisk_debug="0x0400"
# brisk_debug="0xffff"
web_path="/home/nastasi/web/brisk"
ftok_path="/home/nastasi/brisk-priv/ftok/brisk"
proxy_path="/home/nastasi/brisk-priv/proxy/brisk"
usock_path_pfx="/home/nastasi/brisk-priv/brisk"
http_direct="TRUE"
sys_user="www-data"
legal_path="/home/nastasi/brisk-priv/brisk"
prefix_path="/brisk/"
brisk_conf="brisk_spu.conf.pho"
web_only="FALSE"
test_add="FALSE"
# Which machine this installation is for. The daemon and the frontend can sit
# on two different hosts: nginx terminates the tls and proxies the nine game
# endpoints to the daemon over tcp, and serves everything else itself.
#   full    one machine, as it has always been
#   server  the machine that runs brisk-spush: the php the daemon executes,
#           its private directories, no image and no static asset
#   front   the machine that runs nginx and php-fpm: everything it serves,
#           images included, and none of the daemon's private directories
install_mode="full"
# Which http server faces the browser. Only apache reads the .htaccess files:
# with nginx they protect nothing, and the same rules live in
# system/nginx/brisk.conf instead.
#   apache  install the .htaccess files, as it has always done
#   nginx   install none of them
web_server="apache"
# With -S nginx INSTALL.sh writes the nginx configuration of the site next to
# the brisk configuration, in Etc. The pages the daemon does not serve go to
# php-fpm: where it listens (anything fastcgi_pass accepts) and where the
# tree is on that machine, which is not this one when php-fpm runs on the
# daemon host. An empty fpm_root means the root of the site here.
fpm_pass="unix:/run/php/php8.4-fpm.sock"
fpm_root=""
# With -m server and the daemon on tcp, INSTALL.sh writes in Etc the nftables
# rules that let only the front reach the daemon ports - and php-fpm, when
# the php-fpm of this machine serves the front. front_ip is the address of the
# front as this machine sees it; fpm_pool_glob is where the php-fpm pools are
# looked for.
front_ip=""
fpm_pool_glob="/etc/php/*/fpm/pool.d/*.conf"
#
# functions
function usage () {
    echo
    echo "$1 -h"
    echo "$1 chk                          - run lintian on all ph* files."
    echo "$1 pkg                          - build brisk packages."
    echo "$1 [-W] [-n 3|5] [-c 2|8] [-t <(n>=4)>] [-T <auth_tab>] [-r <appr_tab>] [-G <cert_tab>] [-A <apache-conf>] [-a <auth_file_name>] [-f <conffile>] [-p <outconf>] [-U <usock_path_pfx>] [-D <TRUE|FALSE>] [-u <sys_user>] [-d <TRUE|FALSE>] [-w <web_dir>] [-k <ftok_dir>] [-l <legal_path>] [-y <proxy_path>] [-P <prefix_path>] [-m <full|server|front>] [-S <apache|nginx>] [-F <fpm_pass>] [-B <fpm_root>] [-I <front_ip>] [-x]"
    echo "  -h this help"
    echo "  -f use this config file"
    echo "  -p save preferences in the file"
    echo "  -W web files only"
    echo "  -A server conf (for DocumentRoot) - def. $apache_conf"
    echo "  -R document_root                - def. ricavato da -w meno -P"
    echo "  -c number cards in hand         - def. $card_hand"
    echo "  -n number of players            - def. $players_n"
    echo "  -t number of tables             - def. $tables_n"
    echo "  -r number of appr-only tables   - def. $tables_appr_n"
    echo "  -T number of auth-only tables   - def. $tables_auth_n"
    echo "  -G number of cert-only tables   - def. $tables_cert_n"
    echo "  -a authorization file name      - def. \"$brisk_auth_conf\""
    echo "  -d activate dabug               - def. $brisk_debug"
    echo "  -w dir where place the web tree - def. \"$web_path\""
    echo "  -k dir where place ftok files   - def. \"$ftok_path\""
    echo "  -l dir where save logs          - def. \"$legal_path\""
    echo "  -y dir where place proxy files  - def. \"$proxy_path\""
    echo "  -P prefix path                  - def. \"$prefix_path\""
    echo "  -C config filename              - def. \"$brisk_conf\""
    echo "  -U socket path prefix, or       - def. \"$usock_path_pfx\""
    echo "     tcp://<host>:<port> to put the daemon on another machine"
    echo "  -D nginx speaks http with the daemon - def. \"$http_direct\""
    echo "     (FALSE: the frontend hands over the descriptor, see WARNING.txt)"
    echo "  -u system user to run brisk dae - def. \"$sys_user\""
    echo "  -m machine role: full|server|front - def. \"$install_mode\""
    echo "     (server: no images, no static; front: no private dirs)"
    echo "  -S http server: apache|nginx    - def. \"$web_server\""
    echo "     (nginx: no .htaccess installed, nginx conf written in Etc)"
    echo "  -F php-fpm address for nginx    - def. \"$fpm_pass\""
    echo "     (e.g. 10.0.0.6:9000 when php-fpm runs on the daemon machine)"
    echo "  -B site root on the php-fpm host - def. the root of the site here"
    echo "  -I address of the front machine - def. \"$front_ip\""
    echo "     (-m server with -U tcp://: nftables rules written in Etc)"
    echo "  -x copy tests as normal php     - def. \"$test_add\""
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
        pp="$(dirname "$pp")"
    done

    return 1
}

# Put a freshly generated file in place: left alone when nothing changed,
# otherwise the previous version is kept as .old, because these files are
# also the ones somebody may have touched by hand.
function ngx_put() {
    local tmp dst
    tmp="$1"
    dst="$2"

    if [ -f "$dst" ] && cmp -s "$tmp" "$dst"; then
        rm -f "$tmp"
        echo "  $dst unchanged"
    elif [ -f "$dst" ]; then
        mv "$dst" "$dst.old"
        mv "$tmp" "$dst"
        echo "  $dst written, the previous one kept as $(basename "$dst").old"
    else
        mv "$tmp" "$dst"
        echo "  $dst written"
    fi
}

# The name of the installation, from the prefix: "/brisk26/" -> "brisk26".
# It names the generated files and, made safe for identifiers, the nginx
# upstream and map variable and the nftables table.
function inst_name() {
    echo "$prefix_path" | sed 's@^/@@g;s@/$@@g;s@/@_@g;'
}

function inst_var() {
    inst_name | sed 's/[^A-Za-z0-9_]/_/g'
}

# USOCK_POOL_N as the installed tree defines it
function pool_size() {
    local n

    n="$(sed -n "s/^define('USOCK_POOL_N', *\([0-9]\+\)).*/\1/p" "${web_path}__/spush/brisk-spush.phh")"
    echo "${n:-10}"
}

# The tcp ports the php-fpm pools of this machine listen on for other
# machines. php-fpm serves the front only when it listens on tcp on an
# address that is not loopback: a unix socket, or 127.0.0.1, serves this
# machine alone. Nothing printed means php-fpm does not serve the front.
function fpm_front_ports() {
    local f l host port

    for f in $fpm_pool_glob; do
        [ -f "$f" ] || continue
        sed -n 's/^[ \t]*listen[ \t]*=[ \t]*\([^ \t;]*\).*/\1/p' "$f"
    done | while read l; do
        case "$l" in
            /*) continue ;;                    # unix socket
            *:*) host="${l%:*}"; port="${l##*:}" ;;
            *) host=""; port="$l" ;;            # port alone: every address
        esac
        echo "$port" | grep -q '^[0-9]\+$' || continue
        case "$host" in
            127.*|"[::1]"|localhost) continue ;;
        esac
        echo "$port"
    done | sort -un
}

# The nftables rules of the daemon machine of a split installation, written
# in Etc as <name>.nft, to be copied by hand into /etc/nftables.d/. Over tcp
# the daemon believes X-Real-Ip, which the bans are built on, and its control
# channel holds the whole daemon up to 3 seconds per connection; php-fpm on
# tcp runs any php file of this machine for whoever reaches it. Only the
# front may reach the pool and php-fpm, and nobody but this machine the
# control channel.
function nft_conf_gen() {
    local name var hp port pool_n fpm_ports ports_front ports_all nft_f tmp saddr

    name="$(inst_name)"
    var="$(inst_var)"
    nft_f="$etc_path/${name}.nft"
    hp="${usock_path_pfx#*://}"
    port="${hp##*:}"
    pool_n="$(pool_size)"

    fpm_ports="$(fpm_front_ports | tr '\n' ' ' | sed 's/ *$//;s/ /, /g')"
    ports_front="${port}-$((port + pool_n - 1))"
    ports_all="${port}-$((port + pool_n))"
    if [ -n "$fpm_ports" ]; then
        ports_front="${ports_front}, ${fpm_ports}"
        ports_all="${ports_all}, ${fpm_ports}"
    fi
    case "$front_ip" in
        *:*) saddr="ip6 saddr" ;;
        *)   saddr="ip saddr" ;;
    esac

    tmp="$(mktemp)"
    {
        echo "#!/usr/sbin/nft -f"
        echo "# brisk /${name}/ - nftables rules of the daemon machine."
        echo "# Generated by INSTALL.sh, do not edit: run INSTALL.sh again instead."
        echo "# Copy it into /etc/nftables.d/, include that directory from"
        echo '# /etc/nftables.conf (include "/etc/nftables.d/*.nft") and load it:'
        echo "#   nft -c -f /etc/nftables.d/${name}.nft && nft -f /etc/nftables.d/${name}.nft"
        echo "#"
        echo "# The table accepts by default and drops only the ports of brisk. With a"
        echo "# firewall whose policy is drop, an accept here is not enough: the two"
        echo "# accept rules have to go into that chain."
        echo "#"
        echo "#   daemon pool     ${port}-$((port + pool_n - 1))   the front only"
        echo "#   control channel $((port + pool_n))         this machine only (usermgmt.php)"
        if [ -n "$fpm_ports" ]; then
            echo "#   php-fpm         ${fpm_ports}         the front only"
        else
            echo "#   php-fpm         not listed: no pool listens on tcp for other machines"
        fi
        echo
        echo "table inet ${var} {"
        echo "    chain input {"
        echo "        type filter hook input priority filter; policy accept;"
        echo
        echo "        # the daemon listens on its own address, not on 127.0.0.1: the"
        echo "        # connections of this machine to itself come in from lo"
        echo "        iif lo accept"
        echo
        echo "        ${saddr} ${front_ip} tcp dport { ${ports_front} } accept"
        echo
        echo "        tcp dport { ${ports_all} } drop"
        echo "    }"
        echo "}"
    } > "$tmp"
    ngx_put "$tmp" "$nft_f"
    if [ -n "$fpm_ports" ]; then
        echo "  php-fpm serves the front from here (port ${fpm_ports}): included"
    else
        echo "  php-fpm does not listen on tcp here: only the daemon ports are covered."
        echo "  Configure php-fpm first and run INSTALL.sh again if it has to serve the front."
    fi
}

# The nginx configuration of the site, for direct mode (-D TRUE), written in
# Etc beside the brisk configuration. system/nginx/brisk.conf is the same
# thing as a commented template; this one comes out already aligned with the
# parameters of the installation. Three files, because nginx wants their
# parts in three different places:
#
#   nginx-<name>.conf         http level: the upgrade map and the upstream
#   nginx-<name>-server.conf  inside the server {} that terminates the tls
#   nginx-<name>-daemon.inc   included by each of the nine daemon urls
#
# <name> comes from the prefix, and so do the names of the map variable and
# of the upstream: two installations, or another site with its own
# $connection_upgrade, can share one nginx without a duplicate definition.
function nginx_conf_gen() {
    local name var pfx fpmr pool_n hp host port i f tmp
    local http_f srv_f inc_f prbuf ngx ngx_ver etc_rel

    name="$(inst_name)"
    var="$(inst_var)"
    pfx="/$(echo "$prefix_path" | sed 's:^/*::;s:/*$::')"
    fpmr="$(echo "${fpm_root:-$document_root}" | sed 's:/*$::')"

    http_f="$etc_path/nginx-${name}.conf"
    srv_f="$etc_path/nginx-${name}-server.conf"
    inc_f="$etc_path/nginx-${name}-daemon.inc"

    pool_n="$(pool_size)"

    # proxy_request_buffering exists from nginx 1.7.11: older ones refuse
    # the whole configuration with "unknown directive". When the nginx of
    # this machine can be asked, the line is commented out for it.
    prbuf="proxy_request_buffering off;"
    for ngx in nginx /usr/sbin/nginx; do
        if command -v "$ngx" >/dev/null 2>&1; then
            ngx_ver="$("$ngx" -v 2>&1 | sed -n 's@.*nginx/\([0-9.]*\).*@\1@p')"
            break
        fi
    done
    if [ -n "$ngx_ver" ] && \
       [ "$(printf '%s\n1.7.11\n' "$ngx_ver" | sort -V | head -1)" != "1.7.11" ]; then
        prbuf="# proxy_request_buffering off;   # needs nginx >= 1.7.11, here $ngx_ver"
    fi

    # --- http level ---
    tmp="$(mktemp)"
    {
        echo "# brisk ${pfx}/ - nginx, http level. Generated by INSTALL.sh, do not edit:"
        echo "# run INSTALL.sh again instead. Include it at http level, for example"
        echo "#   ln -s $http_f /etc/nginx/conf.d/"
        echo
        echo "map \$http_upgrade \$${var}_connection_upgrade {"
        echo "    default upgrade;"
        echo "    ''      close;"
        echo "}"
        echo
        echo "# max_fails=0: when the daemon restarts nginx would mark every member"
        echo "# as dead and answer 502 \"no live upstreams\" even after it is back."
        echo "upstream ${var}_daemon {"
        if echo "$usock_path_pfx" | grep -q '://'; then
            hp="${usock_path_pfx#*://}"
            host="${hp%:*}"
            port="${hp##*:}"
            for i in $(seq 0 $((pool_n - 1))); do
                echo "    server ${host}:$((port + i)) max_fails=0;"
            done
        else
            for i in $(seq 0 $((pool_n - 1))); do
                echo "    server unix:${usock_path_pfx}${i}.sock max_fails=0;"
            done
        fi
        echo "}"
    } > "$tmp"
    ngx_put "$tmp" "$http_f"

    # --- the nine daemon urls ---
    tmp="$(mktemp)"
    cat > "$tmp" <<EOF
# brisk ${pfx}/ - directives of every url served by the daemon.
# Generated by INSTALL.sh, do not edit: run INSTALL.sh again instead.
# brisk is a comet application: the response of index_rd.php is a stream
# that stays open for hours, none of these lines is decorative.

proxy_pass              http://${var}_daemon;
proxy_http_version      1.1;
# without this nginx buffers the response and the comet stream never arrives
proxy_buffering         off;
# the daemon reads the POST body by itself
${prbuf}
proxy_read_timeout      3600s;
proxy_send_timeout      3600s;
# the daemon already compresses by itself when the client says so
gzip                    off;

proxy_set_header        Host              \$host;
# the daemon takes the player address from X-Real-Ip: bans and blacklist
# are built on it
proxy_set_header        X-Real-Ip         \$remote_addr;
proxy_set_header        X-Forwarded-Proto \$scheme;
proxy_set_header        Upgrade           \$http_upgrade;
proxy_set_header        Connection        \$${var}_connection_upgrade;

# the courtesy page when the daemon cannot be reached: static, served from
# this machine, because error.php needs php-fpm and the database, which may
# be down together with the daemon
error_page              502 503 504 ${pfx}/daemon-down.html;
EOF
    ngx_put "$tmp" "$inc_f"

    # --- inside the server {} ---
    tmp="$(mktemp)"
    {
        echo "# brisk ${pfx}/ - nginx, inside the server {} that terminates the tls."
        echo "# Generated by INSTALL.sh, do not edit: run INSTALL.sh again instead."
        echo "#   server {"
        echo "#       listen 443 ssl;"
        echo "#       ..."
        echo "#       include $srv_f;"
        echo "#   }"
        echo
        echo "location = ${pfx} { return 301 ${pfx}/; }"
        echo
        echo "# the urls the daemon serves: exact matches, they win over everything"
        for f in index.php index_wr.php index_rd.php index_rd_wss.php \
                 briskin5/index.php briskin5/index_wr.php briskin5/index_rd.php \
                 briskin5/index_rd_wss.php briskin5/briskin5/index.php; do
            printf "location = %-38s { include %s; }\n" "${pfx}/$f" "$inc_f"
        done
        echo
        echo "# ^~ : the regex locations of the rest of the site (a \"location ~ \\.php\$\""
        echo "# of its own) must not take the requests of ${pfx}/"
        echo "location ^~ ${pfx}/ {"
        echo "    root  ${document_root};"
        echo "    index index.php;"
        echo
        echo "    # nginx does not read .htaccess files: these replace them. Without"
        echo "    # them the .phh/.pho, not associated with php, download as text."
        echo "    location ^~ ${pfx}/Obj/          { return 404; }"
        echo "    location ^~ ${pfx}/spush/        { return 404; }"
        echo "    location ^~ ${pfx}/briskin5/Obj/ { return 404; }"
        echo "    location ~ \\.(phh|pho)\$          { return 404; }"
        echo "    location ~ /\\.                   { return 404; }"
        echo
        echo "    # every other php page"
        echo "    location ~ \\.php\$ {"
        echo "        include       fastcgi_params;"
        echo "        fastcgi_pass  ${fpm_pass};"
        echo "        fastcgi_param SCRIPT_FILENAME ${fpmr}\$fastcgi_script_name;"
        echo "        fastcgi_param DOCUMENT_ROOT   ${fpmr};"
        echo "    }"
        echo
        echo "    location ~* \\.(js|css)\$ {"
        echo "        expires 1d;"
        echo "        add_header Cache-Control \"public, must-revalidate\";"
        echo "    }"
        echo "    location ~* \\.(png|jpe?g|gif|mp3|swf)\$ {"
        echo "        expires 4d;"
        echo "    }"
        echo "}"
        # Etc holds the database credentials in clear: when it falls inside
        # the root of the site it has to be denied explicitly.
        case "$etc_path" in
            "$document_root"/*)
                etc_rel="${etc_path#$document_root}"
                echo
                echo "# the brisk configuration, with the database credentials in clear"
                echo "location ^~ ${etc_rel}/ { return 404; }"
                ;;
        esac
        # docroot/: index.php asks for these at the root of the site
        if [ -d docroot ]; then
            echo
            echo "# index.php asks for these at the root of the site: they hold for the"
            echo "# whole server {}, not only for ${pfx}/"
            for f in $(find docroot -maxdepth 1 -type f ! -name 'README' | sort); do
                echo "location = /$(basename "$f") { root ${document_root}; }"
            done
        fi
    } > "$tmp"
    ngx_put "$tmp" "$srv_f"

    if [ -n "$ngx_ver" ]; then
        echo "  written for the nginx found here, $ngx_ver: check it with nginx -t"
    else
        echo "  no nginx found here to ask the version: written for nginx >= 1.7.11"
    fi
}

#
#  MAIN
#
declare -a nam
if [ "$1" = "chk" ]; then
    set -e
    oldifs="$IFS"
    IFS='
'
    for i in $(find -name '*.pho' -o -name '*.phh' -o -name '*.php'); do
        php -l $i
    done

    taggit="$(git describe --tags | sed 's/^v//g')"
    tagphp="$(grep "^\$G_brisk_version = " web/Obj/brisk.phh | sed 's/^[^"]\+"//g;s/".*//g')" # ' emacs hell
    if [ "$taggit" != "$tagphp" ]; then
        echo
	echo "WARNING: taggit: [$taggit] tagphp: [$tagphp]"
        echo
    fi
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
    nam_idx=0
    nam[$nam_idx]="brisk_${tag}.tgz"
    nam_idx=$((nam_idx + 1))
    nam[$nam_idx]="brisk-img_${tag}.tgz"

    if [ -d ../curl-de-sac ]; then
       nam_idx=$((nam_idx + 1))
       nam[$nam_idx]="curl-de-sac_${tag}.tgz"
    fi
    pkg_list=""
    sep=""
    for i in ${nam[@]}; do
        pkg_list="${pkg_list}${sep}${i}"
        sep=", "
    done
    echo "Build packages ${pkg_list}."
    read -p "Proceed [y/n]: " a
    if [ "$a" != "y" -a  "$a" != "Y" ]; then
        exit 1
    fi
    git archive --format=tar --prefix=brisk-${tag}/brisk/ $tag | gzip > ../$nam1
    cd ../brisk-img
    git archive --format=tar --prefix=brisk-${tag}/brisk-img/ $tag | gzip > ../$nam2
    cd -
    if [ -d ../curl-de-sac ]; then
        cd ../curl-de-sac
        git archive --format=tar --prefix=brisk-${tag}/curl-de-sac/ $tag | gzip > ../$nam3
        cd -
    fi
    exit 0
fi

if [ -f "$CONFIG_FILE" ]; then
   source "$CONFIG_FILE"
   conffile_in="$CONFIG_FILE"
fi

if [ "x$prefix_path" = "x" ]; then
   prefix_path="$web_path"
fi

action=""
while [ $# -gt 0 ]; do
    # echo aa $1 xx $2 bb
    conffile=""
    case $1 in
        -A*) apache_conf="$(get_param "-A" "$1" "$2")"; sh=$?;;
        -R*) document_root_in="$(get_param "-R" "$1" "$2")"; sh=$?;;
        -f*) conffile="$(get_param "-f" "$1" "$2")"; sh=$?;;
        -p*) outconf="$(get_param "-p" "$1" "$2")"; sh=$?;;
        -c*) card_hand="$(get_param "-c" "$1" "$2")"; sh=$?;;
        -n*) players_n="$(get_param "-n" "$1" "$2")"; sh=$?;;
        -t*) tables_n="$(get_param "-t" "$1" "$2")"; sh=$?;;
        -r*) tables_appr_n="$(get_param "-r" "$1" "$2")"; sh=$?;;
        -T*) tables_auth_n="$(get_param "-T" "$1" "$2")"; sh=$?;;
        -G*) tables_cert_n="$(get_param "-G" "$1" "$2")"; sh=$?;;
        -a*) brisk_auth_conf="$(get_param "-a" "$1" "$2")"; sh=$?;;
        -d*) brisk_debug="$(get_param "-d" "$1" "$2")"; sh=$?;;
        -w*) web_path="$(get_param "-w" "$1" "$2")"; sh=$?;;
        -k*) ftok_path="$(get_param "-k" "$1" "$2")"; sh=$?;;
        -y*) proxy_path="$(get_param "-y" "$1" "$2")"; sh=$?;;
        -P*) prefix_path="$(get_param "-P" "$1" "$2")"; sh=$?;;
        -C*) brisk_conf="$(get_param "-C" "$1" "$2")"; sh=$?;;
        -l*) legal_path="$(get_param "-l" "$1" "$2")"; sh=$?;;
        -U*) usock_path_pfx="$(get_param "-U" "$1" "$2")"; sh=$?;;
        -D*) http_direct="$(get_param "-D" "$1" "$2")"; sh=$?;;
        -u*) sys_user="$(get_param "-u" "$1" "$2")"; sh=$?;;
        -m*) install_mode="$(get_param "-m" "$1" "$2")"; sh=$?;;
        -S*) web_server="$(get_param "-S" "$1" "$2")"; sh=$?;;
        -F*) fpm_pass="$(get_param "-F" "$1" "$2")"; sh=$?;;
        -B*) fpm_root="$(get_param "-B" "$1" "$2")"; sh=$?;;
        -I*) front_ip="$(get_param "-I" "$1" "$2")"; sh=$?;;
        system) action=system ; sh=1;;
        -W) web_only="TRUE";;
        -x) test_add="TRUE";;
        -h) usage $0; exit 0;;
	*) usage $0; exit 1;;
    esac
    if [ ! -z "$conffile" ]; then
        if [ ! -f "$conffile" ]; then
            echo "config file [$conffile] not found"
   	    exit 1
        fi
        . "$conffile"
        conffile_in="$conffile"
    fi
    shift $sh
done

#
#  Show parameters
#
echo "    outconf:    \"$outconf\""
echo "    apache_conf:\"$apache_conf\""
echo "    card_hand:   $card_hand"
echo "    players_n:   $players_n"
echo "    tables_n:    $tables_n"
echo "    tables_appr_n: $tables_appr_n"
echo "    tables_auth_n: $tables_auth_n"
echo "    tables_cert_n: $tables_cert_n"
echo "    brisk_auth_conf: \"$brisk_auth_conf\""
echo "    brisk_debug:\"$brisk_debug\""
echo "    web_path:   \"$web_path\""
echo "    ftok_path:  \"$ftok_path\""
echo "    legal_path: \"$legal_path\""
echo "    proxy_path: \"$proxy_path\""
echo "    prefix_path:\"$prefix_path\""
echo "    brisk_conf: \"$brisk_conf\""
echo "    usock_path_pfx: \"$usock_path_pfx\""
echo "    http_direct: \"$http_direct\""
echo "    sys_user:   \"$sys_user\""
echo "    web_only:   \"$web_only\""
echo "    install_mode: \"$install_mode\""
echo "    web_server: \"$web_server\""
echo "    fpm_pass:   \"$fpm_pass\""
echo "    fpm_root:   \"$fpm_root\""
echo "    front_ip:   \"$front_ip\""
echo "    test_add:   \"$test_add\""

if [ ! -z "$outconf" ]; then
  (
    echo "#"
    echo "#  Produced automatically by brisk::INSTALL.sh"
    echo "#"
    echo "apache_conf=$apache_conf"
    echo "card_hand=$card_hand"
    echo "players_n=$players_n"
    echo "tables_n=$tables_n"
    echo "tables_appr_n=$tables_appr_n"
    echo "tables_auth_n=$tables_auth_n"
    echo "tables_cert_n=$tables_cert_n"
    echo "brisk_auth_conf=\"$brisk_auth_conf\""
    echo "brisk_debug=\"$brisk_debug\""
    echo "web_path=\"$web_path\""
    echo "ftok_path=\"$ftok_path\""
    echo "proxy_path=\"$proxy_path\""
    echo "legal_path=\"$legal_path\""
    echo "prefix_path=\"$prefix_path\""
    echo "brisk_conf=\"$brisk_conf\""
    echo "usock_path_pfx=\"$usock_path_pfx\""
    echo "http_direct=\"$http_direct\""
    echo "sys_user=\"$sys_user\""
    echo "web_only=\"$web_only\""
    echo "install_mode=\"$install_mode\""
    echo "web_server=\"$web_server\""
    echo "fpm_pass=\"$fpm_pass\""
    echo "fpm_root=\"$fpm_root\""
    echo "front_ip=\"$front_ip\""
    echo "test_add=\"$test_add\""
  ) > "$outconf"
fi

max_players=$((40 + players_n * tables_n))

if [ "$action" = "system" ]; then
    scrname="$(echo "$prefix_path" | sed 's@^/@@g;s@/$@@g;s@/@_@g;')"
    echo
    echo "script name:  [$scrname]"
    echo "brisk path:   [$web_path]"
    echo "private path: [$legal_path]"
    echo "system user:  [$sys_user]"
    echo
    read -p "press enter to continue" sure
    cp bin/brisk-init.sh brisk-init.sh.wrk
    sed -i "s@^BPATH=.*@BPATH=\"${web_path}\"@g;s@^PPATH=.*@PPATH=\"${legal_path}\"@g;s@^SSUFF=.*@SSUFF=\"${scrname}\"@g;s@^BUSER=.*@BUSER=\"${sys_user}\"@g" brisk-init.sh.wrk

    su -c "cp brisk-init.sh.wrk /etc/init.d/${scrname}"

    rm brisk-init.sh.wrk
    echo
    echo "... DONE."
    echo "DON'T FORGET: after the first installation you MUST configure your run-levels accordingly"
    echo
    echo "Example: su -c 'update-rc.d $scrname defaults'"
    echo
    exit 0
fi
#
#  Pre-check
#
# check for etc path existence
dsta="$(dirname "$web_path")"
etc_path="$(searchetc "$dsta" Etc)"
if [ $? -ne 0 ]; then
    echo "Etc directory not found"
    exit 1
fi

IFS='
'
#
#  Installation
#
ftokk_path="${ftok_path}k"

if [ $card_hand -lt 2 -o $card_hand -gt 8 ]; then
    echo "card_hand ($card_hand) out of range (2 <= c <= 8)"
    exit 1
fi

if [ $players_n -ne 3 -a $players_n -ne 5 ]; then
    echo "players_n ($players_n) out of range (3|5)"
    exit 1
fi

case "$install_mode" in
    full|server|front) ;;
    *) echo "install_mode (\"$install_mode\") must be full, server or front"; exit 1;;
esac
# The frontend machine has no daemon, so none of the private directories the
# daemon needs - ftok, legal, proxy - makes sense there. That is exactly what
# -W already meant, so "front" simply implies it.
if [ "$install_mode" = "front" ]; then
    web_only="TRUE"
fi

case "$web_server" in
    apache|nginx) ;;
    *) echo "web_server (\"$web_server\") must be apache or nginx"; exit 1;;
esac

if [ "$http_direct" != "TRUE" -a "$http_direct" != "FALSE" ]; then
    echo "http_direct ($http_direct) out of range (TRUE|FALSE)"
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
    for i in $(seq 0 $max_players); do
        touch ${ftokk_path}/user$i
        chmod 666 ${ftokk_path}/user$i
    done

    if [ ! -d ${ftokk_path}/bin5 ]; then
        mkdir ${ftokk_path}/bin5
        chmod 777 ${ftokk_path}/bin5
    fi

    for i in $(seq 0 $((tables_n - 1))); do
        if [ ! -d ${ftokk_path}/bin5/table$i ]; then
            mkdir ${ftokk_path}/bin5/table$i
        fi
        chmod 777 ${ftokk_path}/bin5/table$i
        touch ${ftokk_path}/bin5/table$i/table
        chmod 666 ${ftokk_path}/bin5/table$i/table
        for e in $(seq 0 4); do
            touch ${ftokk_path}/bin5/table$i/user$e
            chmod 666 ${ftokk_path}/bin5/table$i/user$e
        done
        # create subdirectories in proxy path
        if [ ! -d ${proxy_path}/bin5/table$i ]; then
            mkdir -p ${proxy_path}/bin5/table$i
        fi
    done
    chmod -R 777 ${proxy_path}/bin5

    # The donation button is a fragment of html that index.php reads from
    # FTOK_PATH. It is installed only if it is not there already, so as not to
    # overwrite the one of the installation: whoever does not want it simply
    # does not put it there.
    if [ -f data/brisk_donate.txt -a ! -f "${ftokk_path}/brisk_donate.txt" ]; then
        install -m 644 data/brisk_donate.txt "${ftokk_path}/brisk_donate.txt"
        echo "  installed brisk_donate.txt in ${ftok_path}"
    fi

    mkdir -p "${legal_path}"
    chmod 777 "${legal_path}"
fi

bsk_busting="$(git rev-parse --short HEAD 2>/dev/null|| true)"
if [ "$bsk_busting" = "" ]; then
    bsk_busting=$(grep '^\$G_brisk_version'  web/Obj/brisk.phh | sed 's/^[^"'"'"']*["'"'"']/v/g;s/["'"'"'].*//g')
fi
if [ "$bsk_busting" = "" ]; then
    echo "Retreiving bsk_busting failed"
    exit 1
fi

install -d ${web_path}__
for i in $(find web -type d | grep '/' | sed 's/^....//g'); do
    install -d ${web_path}__/$i
done

# The php goes on both machines, and so do the .js. The frontend serves
# admin.php, usermgmt.php and the others with php-fpm, and those include the
# same Obj/ tree: usermgmt.php and mailmgr.php even reach spush/brisk-spush.phh
# for USOCK_PATH_PFX. The .js are needed daemon side too, because index.php
# decides whether to emit the custom.js tag by looking for the file on disk.
#
# What the daemon does not need is what only a browser ever asks for: the
# stylesheets, the sounds and, further down, the images of brisk-img and the
# files of docroot/.
find_names=( -name '.htaccess' -o -name '*.php' -o -name '*.phh' -o -name '*.pho'
             -o -name '*.js' -o -name 'LICENSE' -o -name 'VENDOR.txt'
             -o -name 'terms-of-service*' )
if [ "$install_mode" != "server" ]; then
    find_names+=( -o -name '*.css' -o -name '*.mp3' -o -name '*.swf'
                  -o -name 'daemon-down.html' )
fi
for i in $(find web "${find_names[@]}" | sed 's/^....//g'); do
    install -m 644 "web/$i" "${web_path}__/$i"
done

# hardlink for nginx managed websocket files.
ln "${web_path}__/xynt_test01.php" "${web_path}__/xynt_test01_wss.php"

if [ "$test_add" = "TRUE" ]; then
    for i in $(find webtest -name '.htaccess' -o -name '*.php' -o -name '*.phh' -o -name '*.pho' -o -name '*.css' -o -name '*.js' -o -name '*.mp3' -o -name '*.swf' -o -name 'terms-of-service*' | sed 's/^........//g'); do
        install -m 644 "webtest/$i" "${web_path}__/$i"
    done
fi

# brisk-spush.php is the daemon itself: on the frontend it would be an entry
# point that nothing there is meant to run. The .phh beside it stays, because
# usermgmt.php and mailmgr.php include it for USOCK_PATH_PFX.
if [ "$install_mode" = "front" ]; then
    rm -f "${web_path}__/spush/brisk-spush.php"
else
    chmod 755 "${web_path}__/spush/brisk-spush.php"
fi

prefix_path_len=$(echo -n "$prefix_path" | wc -c)

if [ $players_n -eq 5 ]; then
   send_time=250
else
   send_time=10
fi

# .js substitutions
sed -i "s/CARD_HAND *= *[0-9]\+/CARD_HAND = $card_hand/g" $(find ${web_path}__ -type f -name '*.js' -exec grep -l 'CARD_HAND *= *[0-9]\+' {} \;)
sed -i "s/PLAYERS_N *= *[0-9]\+/PLAYERS_N = $players_n/g" $(find ${web_path}__ -type f -name '*.js' -exec grep -l 'PLAYERS_N *= *[0-9]\+' {} \;)

sed -i "s/^var G_send_time *= *[0-9]\+/var G_send_time = $send_time/g" $(find ${web_path}__ -type f -name '*.js' -exec grep -l '^var G_send_time *= *[0-9]\+' {} \;)

# .ph[pho] substitutions
sed -i "s/define *( *'PLAYERS_N', *[0-9]\+ *)/define('PLAYERS_N', $players_n)/g" $(find ${web_path}__ -type f -name '*.ph*' -exec grep -l "define *( *'PLAYERS_N', *[0-9]\+ *)" {} \;)

sed -i "s/define *( *'BIN5_CARD_HAND', *[0-9]\+ *)/define('BIN5_CARD_HAND', $card_hand)/g" $(find ${web_path}__ -type f -name '*.ph*' -exec grep -l "define *( *'BIN5_CARD_HAND', *[0-9]\+ *)" {} \;)

sed -i "s/define *( *'BIN5_PLAYERS_N', *[0-9]\+ *)/define('BIN5_PLAYERS_N', $players_n)/g" $(find ${web_path}__ -type f -name '*.ph*' -exec grep -l "define *( *'BIN5_PLAYERS_N', *[0-9]\+ *)" {} \;)

sed -i "s@define *( *'FTOK_PATH',[^)]*)@define('FTOK_PATH', \"$ftok_path\")@g" $(find ${web_path}__ -type f -name '*.ph*' -exec grep -l "define *( *'FTOK_PATH',[^)]*)" {} \;)

sed -i "s@define *( *'SITE_PREFIX',[^)]*)@define('SITE_PREFIX', \"$prefix_path\")@g;
s@define *( *'SITE_PREFIX_LEN',[^)]*)@define('SITE_PREFIX_LEN', $prefix_path_len)@g" ${web_path}__/Obj/sac-a-push.phh

sed -i "s@define *( *'USOCK_PATH_PFX',[^)]*)@define('USOCK_PATH_PFX', \"$usock_path_pfx\")@g;
s@define *( *'SPU_HTTP_DIRECT',[^)]*)@define('SPU_HTTP_DIRECT', $http_direct)@g" ${web_path}__/spush/brisk-spush.phh

sed -i "s@define *( *'TABLES_N',[^)]*)@define('TABLES_N', $tables_n)@g;
s@define *( *'TABLES_APPR_N',[^)]*)@define('TABLES_APPR_N', $tables_appr_n)@g;
s@define *( *'TABLES_AUTH_N',[^)]*)@define('TABLES_AUTH_N', $tables_auth_n)@g;
s@define *( *'TABLES_CERT_N',[^)]*)@define('TABLES_CERT_N', $tables_cert_n)@g;
s@define *( *'BRISK_DEBUG',[^)]*)@define('BRISK_DEBUG', $brisk_debug)@g;
s@define *( *'LEGAL_PATH',[^)]*)@define('LEGAL_PATH', \"$legal_path\")@g;
s@define *( *'PROXY_PATH',[^)]*)@define('PROXY_PATH', \"$proxy_path\")@g;
s@define *( *'BSK_BUSTING',[^)]*)@define('BSK_BUSTING', \"$bsk_busting\")@g;
s@define *( *'BRISK_CONF',[^)]*)@define('BRISK_CONF', \"$brisk_conf\")@g;" ${web_path}__/Obj/brisk.phh

# The define is not in Obj/auth.phh, where this sed looked for it without
# ever finding it and without saying so: -a was silently useless. It is in
# Obj/dbase_file.phh. It is now looked for where it actually is, so that a
# future move does not break the option again.
auth_conf_file="$(find ${web_path}__ -type f -name '*.ph*' -exec grep -l "define *( *'BRISK_AUTH_CONF'" {} \;)"
if [ -z "$auth_conf_file" ]; then
    echo "WARNING: define BRISK_AUTH_CONF not found, -a not applied"
else
    sed -i "s@define *( *'BRISK_AUTH_CONF',[^)]*)@define('BRISK_AUTH_CONF', \"$brisk_auth_conf\")@g" $auth_conf_file
fi

sed -i "s@var \+cookiepath \+= \+\"[^\"]*\";@var cookiepath = \"$prefix_path\";@g" ${web_path}__/commons.js

# the courtesy page of nginx reaches its images and the room through <base>
if [ -f "${web_path}__/daemon-down.html" ]; then
    sed -i "s@<base href=\"[^\"]*\">@<base href=\"$prefix_path\">@" "${web_path}__/daemon-down.html"
fi

sed -i "s@\( \+cookiepath *: *\)\"[^\"]*\" *,@\1 \"$prefix_path\",@g" ${web_path}__/xynt-streaming.js

# The root of the site is needed for two things: substituting
# $DOCUMENT_ROOT in the sources and installing the files from docroot/. It
# used to be derived by grepping DocumentRoot out of the apache configuration
# file, which tied INSTALL.sh to apache (nginx uses "root", not
# "DocumentRoot"), took the first match in any VirtualHost, and did not cope
# with quotes.
#
# Now it is derived from the parameters already known: web_path ends with
# prefix_path, so removing the latter from the former leaves the root. No
# server to ask. An explicit value can still be forced with -R.
if [ ! -z "$document_root_in" ]; then
    document_root="$(echo "$document_root_in" | sed 's:/*$::')"
else
    _pfx="$(echo "$prefix_path" | sed 's:^/*::;s:/*$::')"    # brisk
    _web="$(echo "$web_path"    | sed 's:/*$::')"            # .../web/brisk
    document_root="$(echo "$_web" | sed "s:/*$_pfx\$::")"
    if [ "$document_root" = "$_web" -o -z "$document_root" ]; then
        # web_path does not end with prefix_path: fall back on the server
        # configuration file, accepting both DocumentRoot and root
        document_root="$(grep -iE '^[ \t]*(DocumentRoot|root)[ \t]' "${apache_conf}" 2>/dev/null \
                         | grep -v '^[ \t]*#' | head -1 \
                         | awk '{ print $2 }' | tr -d '";' | sed 's:/*$::')"
    fi
fi
if [ -z "$document_root" ]; then
    echo "Cannot determine the root of the site: use -R <document_root>"
    exit 1
fi
echo "    document_root: \"$document_root\""
sed -i "s@^\(\$DOCUMENT_ROOT *= *[\"']\)[^\"']*\([\"']\)@\1$document_root\2@g" ${web_path}__/spush/*.ph* ${web_path}__/donometer.php

# The files under docroot/ belong in the root of the site, not in the
# subdirectory of the application: index.php references them with a leading
# slash ("/cookie_law.js"), so the browser asks the DocumentRoot for them.
if [ -d docroot ] && [ ! -z "$document_root" ] && [ "$install_mode" != "server" ]; then
    for i in $(find docroot -maxdepth 1 -type f ! -name 'README'); do
        install -m 644 "$i" "${document_root}/$(basename "$i")"
        echo "  installed $(basename "$i") in ${document_root}"
    done
elif [ "$install_mode" = "server" ]; then
    echo "  docroot/ skipped: the browser asks the frontend for those files"
fi

# brisk-img carries every image of the site: the cards, the table, the icons.
# Without it the tree installed here is complete as far as the code goes and
# has no image at all, so the pages come up and every img answers 404. It used
# to be skipped without a word, and a run that had quietly dropped the images
# looked exactly like a good one.
if [ "$install_mode" = "server" ]; then
    # The daemon never serves an image: nginx answers for those off its own
    # root. Pulling brisk-img in here would only copy 800 files nobody asks
    # this machine for.
    echo "  brisk-img skipped: the frontend serves the images"
elif [ -d ../brisk-img ]; then
    cd ../brisk-img
    ./INSTALL.sh -w ${web_path}__
    cd - >/dev/null 2>&1
else
    echo
    echo "WARNING: ../brisk-img not found: INSTALLING A SITE WITHOUT IMAGES."
    echo "         The cards and every other image will answer 404. Put the"
    echo "         brisk-img repository beside this one and run again."
    echo
fi
# curl-de-sac is optional: the code that uses it is guarded by
# defined('CURL_DE_SAC_VERS'), so without it the feature is simply absent.
if [ -d ../curl-de-sac ]; then
    cd ../curl-de-sac
    if [ ! -z "$conffile_in" ]; then
        ./INSTALL.sh -f "$conffile_in" -w ${web_path}__
    else
        ./INSTALL.sh -w ${web_path}__
    fi
    cd - >/dev/null 2>&1
else
    echo "note: ../curl-de-sac not found, the site is installed without it"
fi

# nginx never reads a .htaccess. They come not only from web/ and webtest/
# but also from brisk-img and curl-de-sac, so they are dropped here, once the
# whole tree is in place, rather than filtered out of each install.
if [ "$web_server" = "nginx" ]; then
    find "${web_path}__" -name '.htaccess' -type f -delete
fi

# config file installation or diff
if [ -f "$etc_path/$brisk_conf" ]; then
    echo "Config file $etc_path/$brisk_conf exists."
    echo "=== Dump the diff. ==="
    # diff -u "$etc_path/$brisk_conf" "${web_path}__""/Obj/brisk.conf-templ.pho"
    diff -u <(cat "$etc_path/$brisk_conf" | egrep -v '^//|^#' | grep '\$[a-zA-Z_ ]\+=' | sed 's/ \+= .*/ = /g' | sort | uniq) <(cat "${web_path}__""/Obj/brisk.conf-templ.pho" | egrep -v '^//|^#' | grep '\$[a-zA-Z_ ]\+=' | sed 's/ \+= .*/ = /g' | sort | uniq )
    echo "===   End dump.    ==="
else
    echo "Config file $etc_path/$brisk_conf not exists."
    echo "Install a template."
    cp  "${web_path}__""/Obj/brisk.conf-templ.pho" "$etc_path/$brisk_conf"
fi

# The Etc directory holds the configuration with the database credentials in
# clear, and it falls inside the DocumentRoot: without this file
# "$brisk_conf" can be downloaded as plain text, because the .pho extension
# is not associated with php. Checked on apache 2.4.68: without the deny the
# url /Etc/<conf> answers 200 with the content.
# NOTE: nginx does not read .htaccess files, the same rule has to be written
# in the server configuration.
if [ "$web_server" = "nginx" ]; then
    echo "Etc is NOT protected by a .htaccess with nginx: the nginx configuration"
    echo "has to deny $etc_path itself (the one written by -m full|front does it)."
elif [ ! -f "$etc_path/.htaccess" ]; then
    echo "Protect $etc_path from the web."
    cat > "$etc_path/.htaccess" <<'EOEOF'
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order Deny,Allow
    Deny from All
</IfModule>
EOEOF
fi

# nginx on the daemon machine of a split installation has nothing to do: the
# configuration belongs to the front. And the historic mode needs a
# descriptor handover module this configuration knows nothing about.
if [ "$web_server" = "nginx" ]; then
    if [ "$install_mode" = "server" ]; then
        echo "nginx configuration not written: it goes on the front machine (-m front)."
    elif [ "$http_direct" != "TRUE" ]; then
        echo "nginx configuration not written: -D FALSE needs the descriptor handover"
        echo "module, see WARNING.txt."
    else
        echo "nginx configuration:"
        nginx_conf_gen
    fi
fi

# The daemon machine of a split installation: whatever the http server of the
# front, the ports here have to be closed to everybody but the front.
if [ "$install_mode" = "server" ] && echo "$usock_path_pfx" | grep -q '^tcp://'; then
    if [ -z "$front_ip" ]; then
        echo "nftables rules not written: pass the address of the front with -I."
    else
        echo "nftables rules:"
        nft_conf_gen
    fi
fi

if [ -d ${web_path} ]; then
    mv ${web_path} ${web_path}.old
fi

mv ${web_path}__ ${web_path}
if [ -d ${web_path}.old ]; then
    rm -rf ${web_path}.old
fi
if [ "$web_only" = "FALSE" ]; then
    mv "$ftokk_path" "$ftok_path"
fi
case "$install_mode" in
    server)
        echo
        echo "Installed the SERVER part in ${web_path}: the php the daemon runs and"
        echo "its private directories. No image, no stylesheet, nothing from docroot/:"
        echo "the machine running nginx answers for those. Install it there with -m front."
        echo ;;
    front)
        echo
        echo "Installed the FRONT part in ${web_path}: everything nginx and php-fpm"
        echo "serve, images included. No ftok, legal or proxy directory and no daemon:"
        echo "install those on the daemon machine with -m server."
        echo ;;
esac

if [ -f WARNING.txt ]; then
    echo ; echo "    ==== WARNING ===="
    echo
    cat WARNING.txt
    echo
fi
exit 0
