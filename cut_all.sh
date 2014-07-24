#!/bin/bash

if [ $# -lt 3 ]; then
    echo "feed me with 3 arguments, bye"
    exit -1
fi

echo $1 $2 $3
mkdir $2

rm parallel_cmds.tmp
touch parallel_cmds.tmp
for f in $(ls -1 $1/*.tar.info.csv); do
    echo "python cut_into_scenes.py $f $2 $3" >> parallel_cmds.tmp
done

parallel < parallel_cmds.tmp
