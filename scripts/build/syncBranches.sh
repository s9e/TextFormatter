#!/bin/bash

cd $(dirname $(realpath $0))

msg="Synced from master"

for version in 5.4 5.3;
do

	branch="tmp-$version"
	git branch -D $branch 2> /dev/null
	git checkout -b $branch master
	php patchSources.php $version
	cp $version.composer.json ../../composer.json
	cp $version.travis.yml ../../.travis.yml
	git commit -a --no-verify -m"$msg"
	git checkout dev/php$version
	git merge -Xtheirs -m"$msg" $branch
done

git checkout master

cd ../..
ignore=
for file in $(ls -1A);
do
	if [[ "$file" != .git* && "$file" != "composer.json" && "$file" != "LICENSE" && "$file" != "README.md" && "$file" != "src" && "$file" != ".travis.yml" ]]
	then
		ignore="$ignore$file"$'\n'
	fi
done

for version in 5.5 5.4 5.3
do
	branch="release/php$version"

	if [ "$version" = "5.5" ]
	then
		src="master"
	else
		src="dev/php$version"
	fi

	git checkout master
	git checkout "$branch"
	git merge --squash -Xtheirs "$src"
	echo "$ignore" > .gitignore

	for file in $ignore;
	do
		if [ -a "$file" ]
		then
			git rm -r --cached "$file"
		fi
	done

	git commit -a --no-verify -m"Synced release branch from $src"
done

git checkout master
