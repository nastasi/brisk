#!/bin/bash
grep -r console.log web/* | grep -v ': *//' | grep -v '// OK$' | grep -v '/* console.log'
