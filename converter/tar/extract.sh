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
    echo $name
    tar xf $file cSH2013/v$name/info
    tar xf $file cSH2013/v$name/scenecut
    tar xf $file cSH2013/v$name/stableframe
    mv cSH2013/v$name/info $out/$name.tar.info.csv
    mv cSH2013/v$name/scenecut $out/$name.tar.scenecut.csv
    mv cSH2013/v$name/stableframe $out/$name.tar.stableframe.csv
done

rm -rf cSH2013
