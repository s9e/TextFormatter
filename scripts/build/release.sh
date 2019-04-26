#!/bin/bash

cd "$(dirname $0)"

old_version=$(git tag | sort -rV | head -n1)
new_version=$(php patchComposerVersion.php ${1-release})

if [ $? -gt 0 ]
then
	echo "$new_version"
	exit
fi

msg=$(git log --oneline -1 "$old_version")
old_commit_id=${msg:(-40)}
new_commit_id=$(git rev-parse HEAD)

git log --oneline "$old_commit_id..$new_commit_id" | php generateChangelog.php $new_version $old_commit_id $new_commit_id > /tmp/CHANGELOG.md
cd ../..
cat CHANGELOG.md >> /tmp/CHANGELOG.md
mv /tmp/CHANGELOG.md CHANGELOG.md

git add composer.json CHANGELOG.md                           && \
git commit -S --no-verify -m"Release $new_version [ci skip]" && \
#./scripts/build/syncBranches.sh                              && \
#git checkout release/php5.4                                  && \
git tag -s -m "$new_version" "$new_version"                  && \
git push origin "$new_version"                               && \
git checkout master                                          && \
php scripts/build/patchComposerVersion.php dev               && \
git add composer.json                                        && \
git commit --no-verify -m"Updated dev version [ci skip]"     && \
git push
