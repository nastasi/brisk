#!/bin/bash
echo "select login from bsk_users" | ./sql/builder.sh psql -A -t
