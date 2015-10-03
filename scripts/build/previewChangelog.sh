#!/bin/bash

cd "$(dirname $0)"

old_version=$(git tag | sort -rV | head -n1)
new_version=dev
msg=$(git log --oneline -1 "$old_version")
old_commit_id=${msg:(-40)}
new_commit_id=$(git rev-parse HEAD)

git log --oneline "$old_commit_id..$new_commit_id" | php generateChangelog.php $new_version $old_commit_id $new_commit_id
