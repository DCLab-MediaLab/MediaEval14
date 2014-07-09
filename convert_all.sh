#!/bin/bash

if [ "$#" -ne 2 ]; then 
    echo "Usage $0 <input dir> <output dir>"
    exit -1
fi

inp=$1
out=$2

php converter/json/json_convert.php $inp $out
php converter/subtitle/subtitle_convert.php $inp $out
./converter/tar/tar_convert.sh $inp $out
cd converter/transcript-LIUM; ./build.sh; ./run.sh ../../$inp ../../$out; cd ../..
cd converter/transcript-LIMSI; ./build.sh; ./run.sh ../../$inp ../../$out; cd ../..
cd converter/transcript-NST; ./build.sh; ./run.sh ../../$inp ../../$out; cd ../..
