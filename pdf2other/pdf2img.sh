#!/bin/sh

if [ $# -ne 5 ]; then
    echo "Incorrect paramters!"
    echo "Usage:"
    echo "    sudo $0 pdf_file start_page end_page to_dir shave_geometry"
    exit
fi

file=$1
from=$2
to=$3
dir=$4
geometry=$5

if [ ! -d "$dir" ]; then
    mkdir -m 0777 $dir
fi

if [ ! -d "$dir/png" ]; then
    mkdir -m 0777 $dir/png
fi

if [ ! -d "$dir/txt" ]; then
    mkdir -m 0777 $dir/txt
fi


for i in `seq $from ${to}`;
do
    k=`expr $i - 1`
    convert -density 225 "${file}[$k]" -quality 100 $dir/png/${i}_1.png
    convert -shave $geometry  $dir/png/${i}_1.png -quality 100 $dir/png/$i.png
    rm $dir/png/${i}_1.png
    pdftotext -f $i -l $i ${file}  $dir/txt/$i.txt
    echo "converted p$i"
done

echo "### Done ###"
