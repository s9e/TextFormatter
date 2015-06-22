#!/bin/bash
DIR="$(dirname $(dirname $(realpath $0)))/src"
TARGET="$(dirname $(dirname $(dirname $(realpath $0))))/s9e.github.io/TextFormatter/api"
CONF="<?php return new Sami\\Sami('$DIR',['build_dir'=>'$TARGET','cache_dir'=>__DIR__]);"

if [ ! -d /tmp/sami ]
then
	mkdir /tmp/sami
fi
echo "$CONF" > /tmp/sami/conf.php

cd "$(dirname $(realpath $0))"
if [ ! -f ./sami.phar ];
then
	wget http://get.sensiolabs.org/sami.phar
fi

php sami.phar update /tmp/sami/conf.php -v

cd "$TARGET"
git add .
git commit -m"Updated API docs"
git push