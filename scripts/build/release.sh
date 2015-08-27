#!/bin/bash

cd "$(dirname $0)"

last_version=$(git tag | sort -rV | head -n1)
new_version=$(php patchComposerVersion.php ${1-release})

if [ $? -gt 0 ]
then
	echo "$new_version"
	exit
fi

git log --oneline --since "$last_version" | php generateChangelog.php $last_version $new_version

cd ../..

git add composer.json CHANGELOG.md                        && \
git commit --no-verify -m"Release $new_version [ci skip]" && \
./scripts/build/syncBranches.sh                           && \
git checkout release/php5.3                               && \
git tag "$new_version"                                    && \
git push origin "$new_version"                            && \
git checkout master                                       && \
php scripts/build/patchComposerVersion.php dev            && \
git add composer.json                                     && \
git commit --no-verify -m"Updated dev version [ci skip]"  && \
git push
