#!/bin/bash

git checkout master

ignore=
for file in $(ls -1A);
do
	if [[ "$file" != .git* && "$file" != "composer.json" && "$file" != "LICENSE" && "$file" != "README.md" && "$file" != "src" && "$file" != ".travis.yml" ]]
	then
		ignore="$ignore$file"$'\n'
	fi
done

for version in 5.6 5.5 5.4 5.3
do
	branch="release/php$version"
	git branch -D $branch 2> /dev/null

	if [ "$version" = "5.5" ]
	then
		src="master"
	else
		src="dev/php$version"
	fi

	git checkout -b "$branch" "$src"
	echo "$ignore" > .gitignore

	for file in $ignore;
	do
		if [ -a "$file" ]
		then
			git rm -r --cached --quiet "$file" 2> /dev/null
		fi
	done

	git commit -a --no-verify -m"Initialized release branch from $src" --quiet
done

git checkout master
