#!/bin/bash

cd $(dirname $(dirname $(dirname "$0")))

git checkout master
msg="Synced to $(git rev-parse HEAD)"

# Record the files that are non-essentials and should be ignored, starting with all the README.md
ignore=$(find src -type f -name "*.md")
for file in $(ls -1A);
do
	if [[ "$file" != ".git" && "$file" != "composer.json" && "$file" != "LICENSE" && "$file" != "src" ]]
	then
		ignore="$ignore"$'\n'"$file"
	fi
done

for version in 5.6 5.5 5.4 5.3;
do
	tmp="tmp-$version"
	rel="release/php$version"
	git branch -D "$tmp" 2> /dev/null
	git checkout -b "$tmp" master 2> /dev/null

	if [ -f "scripts/build/$version.composer.json" ]
	then
		cp "scripts/build/$version.composer.json" composer.json
	fi

	OPTIMIZE=1 scripts/build/prepareFiles.sh $version

	for file in $ignore;
	do
		if [ -e "$file" ]
		then
			git rm -rfq --cached "$file" 2> /dev/null
		fi
	done

	git show-branch "$rel" || git branch "$rel"
	patch=$(git diff --no-color "$rel")
	git reset --hard

	if [ -z "$patch" ]
	then
		echo "No patching: $rel is up-to-date"
	else
		git checkout "$rel"
		echo "$patch" | git apply --index --whitespace=nowarn -
		git commit -aq --no-verify -m"$msg"
	fi
done

git checkout master
