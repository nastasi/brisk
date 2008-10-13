#!/bin/bash
IFS='
'
for f in `find | grep '\.ph[ph]$'`; do
    echo "-- file: $f ---------------------------------------"
    st="out"
    
    for l in `egrep '^ *class |^} // end class|^ *function +'  $f`; do
	if [ $st = "out" ]; then
	    echo "$l" | grep -q '^ *class \+' 
	    if [ $? -eq 0 ]; then
		st="in"
		class="`echo "$l" | sed 's/^ *class \+//g; s/ *{ *//g'`"
		continue
	    fi
	elif [ $st = "in" ]; then
	    echo "$l" | grep -q '^} // end class' 
	    if [ $? -eq 0 ]; then
		st="out"
		continue
	    fi
	fi

	fun="`echo "$l" | sed 's/ *function *//g'`"
	funame="`echo "$fun" | sed 's/ *(.*//g'`"
	if [ $st = "out" ]; then
	    echo "ss $f - $fun sssssssssssssssssss" 
	elif [ $st = "in" ]; then
	    echo "xx $f - $class :: $fun xxxxxxxxxxxxxxxxxxxxxx"
	    if [ "$class" = "$funame" ]; then
		# constructor case
		egrep "new *$class" `find | grep '\.ph[ph]$'`
	    else
		egrep -- "$class::$funame\(|->$funame\(" `find | grep '\.ph[ph]$'`
	    fi
	    echo 
	fi
    done
    echo --------------------------------------------------------
done


#grep -r '^ *function \+' 
#grep -r '^ *class' `find -name '*.ph*'`
#grep -ir '} // end class' 