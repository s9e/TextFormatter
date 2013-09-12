#!/bin/bash

cd $(dirname $(realpath $0))

msg="Synced from master"

for version in 5.4 5.3;
do
	git branch -D tmp/$version 2> /dev/null
	git checkout -b tmp/$version master
	php patchSources.php $version
	cp $version.composer.json ../../composer.json
	cp $version.travis.yml ../../.travis.yml
	git commit -a --no-verify -m"$msg"
	git checkout dev/php$version
	git merge -Xtheirs -m"$msg" tmp
done

git checkout master