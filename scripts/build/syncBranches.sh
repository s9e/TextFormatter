#!/bin/bash

cd $(dirname $(dirname $(dirname "$0")))

git checkout master
msg="Synced to $(git rev-parse HEAD)"

ignore=
for file in $(ls -1A);
do
	if [[ "$file" != .git* && "$file" != "composer.json" && "$file" != "LICENSE" && "$file" != "README.md" && "$file" != "src" && "$file" != ".travis.yml" ]]
	then
		ignore="$ignore$file"$'\n'
	fi
done

for version in 5.5 5.4 5.3;
do
	tmp="tmp-$version"
	rel="release/php$version"
	git branch -D "$tmp" 2> /dev/null
	git checkout -b "$tmp" master 2> /dev/null

	for file in $ignore;
	do
		if [ -a "$file" ]
		then
			git rm -rq --cached "$file" 2> /dev/null
		fi
	done
	echo "$ignore" > .gitignore

	if [ -f "scripts/build/$version.composer.json" ]
	then
		cp "scripts/build/$version.composer.json" composer.json
	fi

	php scripts/build/patchSources.php $version
	php scripts/build/optimizeSources.php

	git commit -aq --no-verify -m"$msg"
	git show-branch "$rel" && git checkout "$rel" || git checkout -b "$rel" "$tmp"
	git merge -Xtheirs -m"$msg" "$tmp"
done

git checkout master
