#!/bin/bash
DIR=$(dirname $(dirname $(realpath $0)))
TARGET=$(dirname $DIR)/s9e.github.io/TextFormatter/coverage

cd $DIR

rm -rf $TARGET
phpunit -d memory_limit=500M -c phpunit.xml --exclude-group none --coverage-html $TARGET

REGEXP=s/`echo $(dirname $(dirname $DIR)) | sed -e 's/\\//\\\\\//g'`//g
sed -i $REGEXP $TARGET/*.html

SHA1=`git rev-parse HEAD`
REGEXP='s/(<small>Generated by .*? at )[^.]+/\1<a href="https:\/\/github.com\/s9e\/TextFormatter\/tree\/'$SHA1'">'$SHA1'<\/a>/'
sed -i -r "$REGEXP" $TARGET/*.html

touch $TARGET/.nojekyll