#!/bin/bash

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <data_root_dir> <out_dir>"
    exit -1
fi

root=$1
out=$2

tars=$(ls -1 $root/tar/*.tar)

mkdir -p $out

for file in $tars; do
    name=$(basename $file | sed "s/v\(.*\)\.tar/\1/")
    echo "processing tar $name"
    tar xf $file cSH2013/v$name/info
    tar xf $file cSH2013/v$name/scenecut

    infofile="$out/$name.tar.info.csv"
    echo "filename;length;fps" > $infofile
    echo -n "$name;" >> $infofile
    tail -n 2 cSH2013/v$name/info | tr "\n" ";"  >> $infofile

    cutfile="$out/$name.tar.scenecut.csv"
    echo "filename;frameid" > $cutfile
    for fr in $(tail -n 1 cSH2013/v$name/scenecut); do
        echo -n "$name;" >> $cutfile
        echo "$fr" >> $cutfile
    done
done

rm -rf cSH2013
