#! /bin/bash

exit 0
#
#  all this part is from mopshop and we will use it to construct the brisk database
#
DBHOST=127.0.0.1
DBUSER=mopshop
DBBASE=mopshop
DBPASS=sozopoco
PFX="msh_"

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

#
# MAIN
#
sht=0
if [ -f $HOME/.db.conf ]; then
    source $HOME/.db.conf
fi

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
    ( echo "-- MESG: clean start" ; cat sql.d/*.sql | grep -i '^drop' | tac ; echo "-- MESG: clean end" ;   ) | sqlexe $sht
elif [ "$1" = "build" ]; then
    ( echo "-- MESG: build start" ; cat sql.d/*.sql | grep -iv '^drop' ; echo "-- MESG: build end" ;   ) | sqlexe $sht
elif [ "$1" = "rebuild" ]; then
    ( echo "-- MESG: clean start" ; cat sql.d/*.sql | grep -i '^drop' | tac ; echo "-- MESG: clean end" ; \
      echo "-- MESG: build start" ; cat sql.d/*.sql | grep -iv '^drop' ; echo "-- MESG: build end" ;   ) \
        | sqlexe $sht
elif [ "$1" = "psql" ]; then
   psql -h $DBHOST -U $DBUSER $DBBASE
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
    echo "   ./builder add <filesql>"
    echo "   ./builder dump [dumpfile]"
    echo "   ./builder dumpall [dumpfile]"
    echo "   ./builder all"
    echo "   ./builder help"
fi
