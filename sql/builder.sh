#! /bin/bash

#
#  all this part is from mopshop and we will use it to construct the brisk database
#

if [ -f $HOME/.db.conf ]; then
    source $HOME/.db.conf
else
    DBHOST=127.0.0.1
    DBUSER=brisk
    DBBASE=brisk
    DBPASS=briskpass
    PFX="bsk_"
fi

if [ -f $HOME/.brisk_install ]; then
    source $HOME/.brisk_install
fi


sqlexe () {
    local sht
    sht=$1
    
    if [ $sht -eq 1 ];  then 
        sed "s/#PFX#/$PFX/g" | psql -a -h $DBHOST -U $DBUSER $DBBASE 2>&1 | egrep 'ERROR|^-- MESG' 
    else
        sed "s/#PFX#/$PFX/g" | psql -a -h $DBHOST -U $DBUSER $DBBASE
    fi

    return 0
}

one_or_all() {
    if [ "$1" = "" ]; then
        cat sql.d/*.sql
    else
        cat "$1"
    fi
}

#
# MAIN
#
sht=0

if [ "$1" = "-s" ]; then
    shift
    sht=1
fi

if [ "$1" = "create" ]; then
    echo "su root" 
    su root -c "su postgres -c \"echo \\\"DBUser passwd: $DBPASS\\\" ; createuser -S -D -R -P $DBUSER && createdb -E utf8 -O $DBUSER $DBBASE\"" 
elif [ "$1" = "destroy" ]; then
    echo "su root" 
    su root -c "su postgres -c \"dropdb $DBBASE && dropuser $DBUSER\"" 
elif [ "$1" = "clean" ]; then
    ( echo "-- MESG: clean start" ; one_or_all $2 | grep -i '^drop' | tac ; echo "-- MESG: clean end" ;   ) | sqlexe $sht
elif [ "$1" = "build" ]; then
    ( echo "-- MESG: build start" ; one_or_all $2 | grep -iv '^drop' ; echo "-- MESG: build end" ;   ) | sqlexe $sht
elif [ "$1" = "rebuild" ]; then
    ( echo "-- MESG: clean start" ; one_or_all $2 | grep -i '^drop' | tac ; echo "-- MESG: clean end" ; \
        echo "-- MESG: build start" ; one_or_all $2 | grep -iv '^drop' ; echo "-- MESG: build end" ;   ) \
        | sqlexe $sht
elif [ "$1" = "psql" ]; then
   shift
   psql -h $DBHOST -U $DBUSER $DBBASE $@
elif [ "$1" = "piped" ]; then
   shift
   psql -h $DBHOST -U $DBUSER $DBBASE -t -q -A -F '|' $@
elif [ "$1" = "dump" ]; then
    if [ $# -eq 1 ]; then
        pg_dump -a --inserts -h $DBHOST -U $DBUSER $DBBASE
    else
        pg_dump -a --inserts -h $DBHOST -U $DBUSER $DBBASE > $2
    fi
elif [ "$1" = "dumpall" ]; then
    if [ $# -eq 1 ]; then
        pg_dump -h $DBHOST -U $DBUSER $DBBASE
    else
        pg_dump -h $DBHOST -U $DBUSER $DBBASE > $2
    fi
elif [ "$1" = "add" ]; then
    cat "$2" | psql -h $DBHOST -U $DBUSER $DBBASE
else
    echo " USAGE"
    echo "   ./builder create"
    echo "   ./builder destroy"
    echo "   ./builder clean"
    echo "   ./builder build"
    echo "   ./builder rebuild"
    echo "   ./builder psql"
    echo "   ./builder piped"
    echo "   ./builder add <filesql>"
    echo "   ./builder dump [dumpfile]"
    echo "   ./builder dumpall [dumpfile]"
    echo "   ./builder all"
    echo "   ./builder help"
fi
