#!/bin/bash

#webdata/cast/ txt to csv, separator is ;
DIR=$1
DIR2=$2
#wd="webdata"
if [ -d "$DIR" ]; then
cd "$DIR"
fi
echo "${DIR}"
cd webdata
cd cast
pwd
for i in $(ls *.txt); do
sed -i -e 's/|/;/g' $i
kit=$(echo $i | sed s/.txt/.webdata.csv/)
mv $i $kit
sed -i '1i\actor firstname;actor familyname;character name' $kit
if [ ! -s "$kit" ]; then
echo "actor firstname;actor familyname;character name" >$kit
fi
mv $kit "$DIR2"

done

