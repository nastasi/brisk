#! /bin/bash
# set -x
#
MATCH_DROP='^DROP.*([^-]...|.[^-]..|..[^M].|...[^F])$|^ALTER TABLE.* DROP .*([^-]...|.[^-]..|..[^M].|...[^F])$|^DELETE .*([^-]...|.[^-]..|..[^M].|...[^F])$|--MB$'

DATECUR="$(date +%s)"

#  functions
usage () {
    echo " USAGE"
    echo "   $0 <command> [-d|--dryrun] [<-a|--allfiles>|<-p|--devfiles>] [-s|--short] ..."
    echo "   $0 <-h|--help|help>"
    echo "   commands are:"
    echo "       create"
    echo "       destroy"
    echo "       clean"
    echo "       build"
    echo "       rebuild"
    echo "       psql"
    echo "       piped"
    echo "       add <filesql> [<filesql2> [..]]"
    echo "       del <filesql> [<filesql2> [..]]"
    echo "       res <filesql> [<filesql2> [..]]"
    echo "       dump [dumpfile]"
    echo "       dumpall [dumpfile]"
    echo "       all"
    echo
    echo "The match rule for clean lines is:"
    echo "  [$MATCH_DROP]"
    echo "NOTE: to invert normal 'del' rules add '--MF' (move forward) suffix to each line"
    echo "      to invert normal 'add' rules add '--MB' (move backward) suffix to each line"

    exit $1
}

sqlexe () {
    local sht
    sht=$1

    if [ "$SHORT" = "y" ];  then
        sed "s/#PFX#/$PFX/g;s/#NOW#/$DATECUR/g" | psql -a $pg_args 2>&1 | egrep 'ERROR|^-- MESG|^-- FILE '
    else
        sed "s/#PFX#/$PFX/g;s/#NOW#/$DATECUR/g" | psql -a $pg_args
    fi

    return 0
}

one_or_all() {
    local old_ifs

    old_ifs="$IFS"
    IFS=" "
    for fil in $(
        if [ "$1" ]; then
            echo "$1"
        elif [ "$TYPE_FILES" = "a" ]; then
            echo sql.d/[0-9]*
        elif [ "$TYPE_FILES" = "d" ]; then
            echo sql.d/[0-9]*.{sql,devel}
        else
            echo sql.d/[0-9]*.sql
            fi); do
        echo "-- FILE BEG: $fil"
        cat "$fil"
        echo "-- FILE END: $fil"
    done
    IFS="$old_ifs"
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
            TYPE_FILES=a
            ;;
        -p|--devfiles)
            TYPE_FILES=d
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
        ( echo "-- MESG: clean start" ; one_or_all $2 | egrep "$MATCH_DROP|^-- MESG|^-- FILE " | tac ; echo "-- MESG: clean end" ;   ) | sqlexe
        ;;
    "build")
        ( echo "-- MESG: build start" ; one_or_all $2 | egrep -v "$MATCH_DROP" ; echo "-- MESG: build end" ;   ) | sqlexe
        ;;
    "rebuild")
        ( echo "-- MESG: clean start" ; one_or_all $2 | egrep "$MATCH_DROP|^-- MESG|^-- FILE " | tac ; echo "-- MESG: clean end" ; \
            echo "-- MESG: build start" ; one_or_all $2 | egrep -v "$MATCH_DROP" ; echo "-- MESG: build end" ;   ) \
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
        ( echo "-- MESG: add start" ; cat "$@" | egrep -v "$MATCH_DROP" ; echo "-- MESG: add end" ;   ) | sqlexe
        ;;
    "del")
        ( echo "-- MESG: del start" ; cat "$@" | egrep "$MATCH_DROP|^-- MESG|^-- FILE " | tac ; echo "-- MESG: del end" ;   ) | sqlexe
        ;;
    "res")
        ( echo "-- MESG: res start" ; cat "$@" | egrep "$MATCH_DROP|^-- MESG|^-- FILE " | tac ; cat "$@" | egrep -v "$MATCH_DROP" ; echo "-- MESG: del end" ;   ) | sqlexe
        ;;
    "help"|"-h"|"--help")
        usage 0
        ;;
    *)
        usage 1
        ;;
esac

exit 0
