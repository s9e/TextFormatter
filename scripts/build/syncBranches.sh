#!/bin/bash

msg="Synced from master"

for version in 5.4 5.3;
do
	cd $(dirname $(realpath $0))

	branch="tmp-$version"
	git branch -D $branch 2> /dev/null
	git checkout -b $branch master
	php patchSources.php $version
	cp $version.composer.json ../../composer.json
	cp $version.travis.yml ../../.travis.yml
	git commit -a --no-verify -m"$msg"
	git checkout dev/php$version
	git merge -Xtheirs -m"$msg" $branch

	cd $(dirname $(dirname $(dirname $(realpath $0))))

	git checkout release/php$version
	git merge -Xtheirs -m"$msg" dev/php$version
done

git checkout release/php5.5
git merge -Xtheirs -m"$msg" master

git checkout master