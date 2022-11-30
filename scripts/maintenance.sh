#!/bin/bash

cd "$(dirname $0)"

for file in patch*;
do
	echo -n "Running $file ... ";
	./$file;
done

./generateBundles.php

echo "All done."