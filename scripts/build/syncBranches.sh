#!/bin/bash

cd $(dirname $(realpath $0))
git branch -D tmp 2> /dev/null

msg="Synced from master"

for version in 5.4 5.3;
do
	git checkout -b tmp master
	php $version.convert.php $version
	cp $version.composer.json ../../composer.json
	cp $version.travis.yml ../../.travis.yml
	git commit -a --no-verify -m"$msg"
	git checkout dev/php$version
	git merge -Xtheirs -m"$msg" tmp
	git branch -D tmp
done

git checkout master