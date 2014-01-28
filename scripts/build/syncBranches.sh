#!/bin/bash

cd $(dirname $(dirname $(dirname "$0")))

git checkout master
msg="Synced to $(git rev-parse HEAD)"

for version in 5.5 5.4 5.3;
do
	tmp="tmp-$version"
	rel="release/php$version"
	git branch -D "$tmp" 2> /dev/null
	git checkout -b "$tmp" master 2> /dev/null

	if [ -f "scripts/build/$version.composer.json" ]
	then
		cp "scripts/build/$version.composer.json" composer.json
	fi

	php scripts/build/patchSources.php $version
	php scripts/build/optimizeSources.php

	git show-branch "$rel" || git branch "$rel"
	patch=$(git diff --no-color "$rel")
	git reset --hard

	if [ -z "$patch" ]
	then
		echo "No patching: $rel is up-to-date"
	else
		git checkout "$rel"
		echo "$patch" | git apply --whitespace=nowarn -
		git commit -aq --no-verify -m"$msg"
		git reset --hard master
	fi
done

git checkout master
