#!/bin/bash
IFS='
'
( for i in $(grep -r '^ *function' web | sed 's/.*:[      ]*function[     ]*//g;s/(.*//g'); do
    grep -rw $i web | wc -l | tr -d '\n' 
    echo ": $i"
done ) | sort -n