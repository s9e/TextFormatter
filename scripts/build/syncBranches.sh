#!/bin/bash

cd $(dirname $(dirname $(dirname "$0")))

master=1.4/master
git checkout $master
msg="Synced to $(git rev-parse HEAD)"
branches=

# Record the files that are non-essentials and should be ignored, starting with all the README.md
ignore=$(find src -type f -name "*.md")
for file in $(ls -1A);
do
	if [[ "$file" != ".git" && "$file" != "composer.json" && "$file" != "LICENSE" && "$file" != "src" ]]
	then
		ignore="$ignore"$'\n'"$file"
	fi
done

for fullversion in 5.6.0 5.5.0 5.4.7 5.3.3;
do
	version="${fullversion%.*}"

	tmp="tmp-$version"
	rel="release/php$version"
	branches="$branches $rel"

	git branch -D "$tmp" 2> /dev/null
	git checkout -b "$tmp" $master 2> /dev/null

	scripts/build/prepareFiles.sh $fullversion

	for file in $ignore;
	do
		if [ -e "$file" ]
		then
			git rm -rfq --cached "$file" 2> /dev/null
		fi
	done

	git show-branch "$rel" || git branch "$rel"
	patch=$(git diff --binary --no-color "$rel")
	git reset --hard

	if [ -z "$patch" ]
	then
		echo "No patching: $rel is up-to-date"
	else
		git checkout "$rel"
		echo "$patch" | git apply --binary --index --whitespace=nowarn -
		git commit -aq --no-verify -m"$msg"
	fi
done

git checkout $master
git push origin $branches
