#!/bin/bash
if [ ~/.brisk_install ]; then
    source ~/.brisk_install
fi

act="$1"
if [ "$act" = "sel" -o "$act" = "qsel" -o "$act" = "selcode" -o "$act" = "sma" -o "$act" = "selblo" -o "$act" = "selapp" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    # echo "select * FROM bsk_users WHERE login = '$usr'"
    echo "SELECT gua.login AS guar_login, gua.email AS guar_email , usr.code, usr.login, usr.pass, usr.email, to_hex(usr.type) as type, usr.lintm, usr.mtime, usr.last_dona, usr.supp_comp, usr.tos_vers, usr.disa_reas, usr.guar_code FROM bsk_users AS usr JOIN bsk_users AS gua ON usr.guar_code = gua.code WHERE " | tr -d '\n'
    if [ "$act" = "sel" ]; then
        echo " usr.login = '$usr';"
    elif [ "$act" = "qsel" ]; then
        echo " usr.login LIKE '%${usr}%';"
    elif [ "$act" = "selcode" ]; then
        echo " usr.code = $usr;"
    elif [ "$act" = "sma" ]; then
        echo " usr.email = '$usr';"
    elif [ "$act" = "selblo" ]; then
        echo " char_length(usr.pass) != 32 ORDER BY usr.pass;"
    elif [ "$act" = "selapp" ]; then
# 80000
        echo " usr.pass != 'THE_PASS' AND (usr.type & CAST(X'80000' as integer)) = CAST(X'80000' as integer);"
    else
        echo " usr.login = 'mop';"
    fi
 elif [ "$act" = "add" ]; then
      # "$0 add <user> <pass> <email> <guar> <type_hex>"
      shift
      if [ $# -ne 5 ]; then
          echo "usage: $0 add <user> <pass> <email> <guar> <type_hex>" >&2
          exit 1
      fi
      if ! echo "$5" | grep -qE '^(0[xX])?[0-9a-fA-F]+$'; then
          echo "$0 add: type must be hexadecimal, as 10000 or 0x10000" >&2
          exit 1
      fi
      usr="$(echo "$1" | tr 'A-Z' 'a-z' | sed "s/'/''/g")"
      pass="$(echo "$2" | sed "s/'/''/g")"
      ema="$(echo "$3" | sed "s/'/''/g")"
      guar="$(echo "$4" | sed "s/'/''/g")"
      ty="$(echo "$5" | sed 's/^0[xX]//')"
      # as BriskDB::user_add(): login in lower case, the password as its md5
      # (as user_update_passwd()), no disable reason. The guarantor comes from
      # its login: when it does not exist nothing is inserted ("INSERT 0 0")
      echo "INSERT INTO bsk_users (login, pass, email, type, disa_reas, guar_code, lintm) SELECT '${usr}', md5('${pass}'), '${ema}', CAST(X'${ty}' AS integer), 0, gua.code,
  now() FROM bsk_users AS gua WHERE gua.login = '${guar}' RETURNING code, login, email, to_hex(type) AS type, guar_code;"
elif [ "$act" = "who_guar" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "select * from bsk_users where guar_code = (select code from bsk_users where login = '$usr');"
elif [ "$act" = "guar" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    guar="$(echo "$2" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type, guar_code) = ( (type & ~(CAST (X'90000' as integer))) | (CAST (X'10000' as integer)), (SELECT code FROM bsk_users WHERE login = '${guar}')) WHERE login = '${usr}';"
    # TO REVERT:
    # echo "UPDATE bsk_users SET (type, guar_code) = ( (type & ~(CAST (X'90000' as integer))) | (CAST (X'80000' as integer)), 3) WHERE login = '${usr}';"
elif [ "$act" = "dis" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    # tipically 4
    reas="$2"
    echo "UPDATE bsk_users SET (type, disa_reas) = ( (type & ~(CAST (X'800000' as integer))) | (CAST (X'00800000' as integer)), $reas ) WHERE login = '$usr'; "
elif [ "$act" = "ena" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type, disa_reas) = ( (type & ~(CAST (X'800000' as integer))) & ~(CAST (X'00800000' as integer)), 0 ) WHERE login = '$usr'; "
elif [ "$act" = "cert" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type) = ( type | (CAST (X'00040000' as integer)) ) WHERE login = '$usr'; "
elif [ "$act" = "uncert" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type) = ( type & ~(CAST (X'00040000' as integer)) ) WHERE login = '$usr'; "
elif [ "$act" = "super" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type) = ( (type & ~(CAST (X'00010000' as integer))) | (CAST (X'00020000' as integer)) ) WHERE login = '$usr'; "
elif [ "$act" = "desuper" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (type) = ( (type & ~(CAST (X'00020000' as integer))) | (CAST (X'00010000' as integer)) ) WHERE login = '$usr'; "
elif [ "$act" = "moder" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET type = type | CAST (X'00100000' as integer) WHERE login = '$usr'; "
elif [ "$act" = "unmoder" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET type = type & ~CAST (X'00100000' as integer) WHERE login = '$usr'; "
elif [ "$act" = "pass" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    pass="$2"
    echo "UPDATE bsk_users SET (pass) = ( '$pass' ) WHERE login = '$usr';"
elif [ "$act" = "email" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    email="$2"
    echo "UPDATE bsk_users SET (email) = ( '$email' ) WHERE login = '$usr';"
elif [ "$act" = "depass" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    pfx="$2"
    echo "UPDATE bsk_users SET (pass) = ( '$pfx' || pass ) WHERE login = '$usr';"
elif [ "$act" = "repass" ]; then
    shift
    usr="$(echo "$1" | sed "s/'/''/g")"
    echo "UPDATE bsk_users SET (pass) = ( substring( pass, (char_length(pass) - 31), 32 ) ) WHERE login = '$usr';"
elif [ "$act" = "unet" ]; then
    echo "select ow.login, tg.login, un.* from bsk_usersnet as un, bsk_users as ow, bsk_users as tg WHERE ow.code = un.owner AND tg.code = un.target  order by un.owner;"
else
    egrep "^define\('USER_(FLAG_TY|DIS_REA)" ${web_path}/Obj/user.phh
    egrep "^define\('USER_DIS_REA"  ${web_path}/Obj/user.phh
    echo "$0 add <user> <pass> <email> <type> <guar_code>"
    echo "$0 sel <user>"
    echo "$0 qsel <user> (with like)"
    echo "$0 sma <user> select by email"
    echo "$0 selcode <user> select by code"
    echo "$0 selblo <user> select users with password len not standard"
    echo "$0 selapp return all apprentices"
    echo "$0 add <user> <pass> <email> <guar> <type_hex>  - new user, <guar> is the login of the guarantor"
    echo "$0 who_guar <user>  - list of users guaranted by <user>"
    echo "$0 guar <user> <guar>  - move apprentice to guaranted status"
    echo "$0 cert <user>"
    echo "$0 uncert <user>"
    echo "$0 dis <user> <reas_id>"
    echo "$0 ena <user>"
    echo "$0 super <user>"
    echo "$0 moder <user>  - the user can accept or refuse the new users in usermgmt.php"
    echo "$0 unmoder <user>"
    echo "$0 pass <user> <newpass>"
    echo "$0 email <user> <newemail>"
    echo "$0 depass <user> <prefix>"
    echo "$0 repass <user>"
    echo "$0 unet"
fi
