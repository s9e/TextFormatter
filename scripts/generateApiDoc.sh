#!/bin/bash
ROOT="$(dirname $(dirname $(realpath $0)))"
DIR="$ROOT/src"
TARGET="$(dirname $(dirname $(dirname $(realpath $0))))/s9e.github.io/TextFormatter/api"
CONF="<?php return new Sami\\Sami('$DIR',['build_dir'=>'$TARGET','cache_dir'=>__DIR__.'/sami','store'=>new Sami\\Store\\ArrayStore]);"
TMP_DIR="$(dirname $TARGET)/.cache"

cd "$TMP_DIR"
echo "$CONF" > conf.php
php "$ROOT/vendor/bin/sami.php" update conf.php -v

cd "$DIR"
COMMIT_MSG="Updated API docs to $(git rev-parse HEAD)"

cd "$TARGET"
cd ../..
git add TextFormatter/api
git commit -m"$COMMIT_MSG"
git push --all