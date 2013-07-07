#!/bin/bash

cd $(dirname $(realpath $0))
git branch -D tmp 2> /dev/null

msg="Synced from master"

git checkout -b tmp master
./convert5.4.php 5.4
git commit -a --no-verify -m"$msg"
git checkout dev/php5.4
git merge -Xtheirs -m"$msg" tmp

git branch -D tmp

git checkout -b tmp master
./convert5.4.php 5.4
./convert5.3.php 5.3
git commit -a --no-verify -m"$msg"
git checkout dev/php5.3
git merge -Xtheirs -m"$msg" tmp

git branch -D tmp
git checkout master