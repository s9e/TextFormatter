#!/bin/bash

cd $(dirname $(realpath $0))
git branch -D tmp 2> /dev/null

msg="Synced from master"

git checkout -b tmp master
./5.4.convert.php 5.4
cp 5.4.composer.json ../../composer.json
cp 5.4.travis.yml ../../.travis.yml
git commit -a --no-verify -m"$msg"
git checkout dev/php5.4
git merge -Xtheirs -m"$msg" tmp

git branch -D tmp

git checkout -b tmp master
./5.4.convert.php 5.4
./5.3.convert.php 5.3
cp 5.3.composer.json ../../composer.json
cp 5.3.travis.yml ../../.travis.yml
git commit -a --no-verify -m"$msg"
git checkout dev/php5.3
git merge -Xtheirs -m"$msg" tmp

git branch -D tmp
git checkout master