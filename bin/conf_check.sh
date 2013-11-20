#!/bin/bash
CONFFILE=$HOME/.brisk_install

webbase_get () {
    local p="$1"
 
    while [ "$p" ]; do
        if [ -d "${p}/Etc" ]; then
            echo "${p}/Etc"
            return 0
        fi
        if [ "$p" = "/" ]; then
            return 1
        fi
        p="$(dirname "$p")"
    done
}

if [ -f "$CONFFILE" ]; then
    source "$CONFFILE"
else
    echo "$CONFFILE not found"
    exit 1
fi

WEBBASE="$(webbase_get "$web_path")"
if [ $? -ne 0 ]; then
    echo "Etc directory not found"
    return 1
fi

BRISKCONF="${WEBBASE}/brisk.conf.pho"
if [ ! -f "$BRISKCONF" ]; then
    echo "$BRISKCONF not found"
    exit 2
fi
echo "Check $BRISKCONF with web/Obj/brisk.conf-templ.pho ... " | tr -d '\n'
diff -u <(sed 's/ *=.*//g' web/Obj/brisk.conf-templ.pho | grep -v '^[ 	]*//' | sort) <(sed 's/ *=.*//g' "$BRISKCONF" | grep -v '^[ 	]*//' | sort)
if [ $? -eq 0 ]; then
    echo "vars match."
fi


