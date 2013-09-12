#! /bin/bash

#
#  functions
usage () {
    echo " USAGE"
    echo "   ./builder <command> [-d|--dryrun] [-a|--allfiles] [-s|--short] ..."
    echo "   ./builder <-h|--help|help>"
    echo "   commands are:"
    echo "       create"
    echo "       destroy"
    echo "       clean"
    echo "       build"
    echo "       rebuild"
    echo "       psql"
    echo "       piped"
    echo "       add <filesql>"
    echo "       dump [dumpfile]"
    echo "       dumpall [dumpfile]"
    echo "       all"
    exit $1
}

sqlexe () {
    local sht
    sht=$1

    if [ "$SHORT" = "y" ];  then
        sed "s/#PFX#/$PFX/g" | psql -a $pg_args 2>&1 | egrep 'ERROR|^-- MESG'
    else
        sed "s/#PFX#/$PFX/g" | psql -a $pg_args
    fi

    return 0
}

one_or_all() {
    if [ "$ALL_FILES" = "y" ]; then
        sfx_files='*'
    else
        sfx_files='*.sql'
    fi

    if [ "$1" = "" ]; then
        cat sql.d/$sfx_files
    else
        cat "$1"
    fi
}

#
#  MAIN
#

CMD=$1
shift

while [ $# -gt 0 ]; do
    case $1 in
        -d|--dryrun)
            DRY_RUN=y
            psql () {
                echo "MOCKPSQL params: $@"
                cat
            }
            ;;
        -a|--allfiles)
            ALL_FILES=y
            ;;
        -s|--short)
            SHORT=y
            ;;
        *)
            break
            ;;
    esac
    shift
done

if [ -f $HOME/.brisk-db.conf ]; then
    source $HOME/.brisk-db.conf
elif [ -f $HOME/.db.conf ]; then
    source $HOME/.db.conf
else
    DBHOST=127.0.0.1
    DBUSER=brisk
    DBPORT=5432
    DBBASE=brisk
    DBPASS=briskpass
    PFX="bsk_"
fi

if [ -f $HOME/.brisk_install ]; then
    source $HOME/.brisk_install
fi

pg_args=""
test "$DBHOST" != "" && pg_args="$pg_args -h $DBHOST"
test "$DBUSER" != "" && pg_args="$pg_args -U $DBUSER"
test "$DBPORT" != "" && pg_args="$pg_args -p $DBPORT"
test "$DBBASE" != "" && pg_args="$pg_args $DBBASE"

MATCH_DROP='^drop|^alter table.* drop '

case $CMD in
    "create")
        echo "su root"
        su root -c "su postgres -c \"echo \\\"DBUser passwd: $DBPASS\\\" ; createuser -S -D -R -P $DBUSER && createdb -E utf8 -O $DBUSER $DBBASE\""
        ;;

    "destroy")
        echo "su root"
        su root -c "su postgres -c \"dropdb $DBBASE && dropuser $DBUSER\""
        ;;
    "clean")
        ( echo "-- MESG: clean start" ; one_or_all $2 | egrep -i "$MATCH_DROP" | tac ; echo "-- MESG: clean end" ;   ) | sqlexe
        ;;
    "build")
        ( echo "-- MESG: build start" ; one_or_all $2 | egrep -iv "$MATCH_DROP" ; echo "-- MESG: build end" ;   ) | sqlexe
        ;;
    "rebuild")
        ( echo "-- MESG: clean start" ; one_or_all $2 | egrep -i "$MATCH_DROP" | tac ; echo "-- MESG: clean end" ; \
            echo "-- MESG: build start" ; one_or_all $2 | egrep -iv "$MATCH_DROP" ; echo "-- MESG: build end" ;   ) \
            | sqlexe
        ;;
    "psql")
        psql $pg_args $@
        ;;

    "piped")
        psql $pg_args -t -q -A -F '|' $@
        ;;
    "dump")
        if [ $# -eq 1 ]; then
            pg_dump -a --inserts -h $DBHOST -U $DBUSER $DBBASE
        else
            pg_dump -a --inserts -h $DBHOST -U $DBUSER $DBBASE > $1
        fi
        ;;
    "dumpall")
        if [ $# -eq 1 ]; then
            pg_dump -h $DBHOST -U $DBUSER $DBBASE
        else
            pg_dump -h $DBHOST -U $DBUSER $DBBASE > $1
        fi
        ;;
    "add")
        cat "$1" | sqlexe
        ;;
    "help"|"-h"|"--help")
        usage 0
        ;;
    *)
        usage 1
        ;;
esac

exit 0