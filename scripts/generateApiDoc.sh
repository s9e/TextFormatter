#!/bin/bash
DIR="$(dirname $(dirname $(realpath $0)))/src"
TARGET="$(dirname $(dirname $(dirname $(realpath $0))))/s9e.github.io/TextFormatter/api"
CONF="<?php return new Sami\\Sami('$DIR',['build_dir'=>'$TARGET','cache_dir'=>__DIR__.'/sami','store'=>new Sami\\Store\\ArrayStore]);"
TMP_DIR="$(dirname $TARGET)/.cache"

if [ ! -d "$TMP_DIR/sami" ]
then
	mkdir -p "$TMP_DIR/sami"
fi

cd "$TMP_DIR"
if [ ! -f ./sami.phar ];
then
	wget https://get.sensiolabs.org/sami.phar
fi

echo "$CONF" > conf.php
php sami.phar update conf.php -v

cd "$DIR"
COMMIT_MSG="Updated API docs to $(git rev-parse HEAD)"

cd "$TARGET"
cd ../..
git add TextFormatter/api
git commit -m"$COMMIT_MSG"
git push --all