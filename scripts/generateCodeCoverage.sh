#!/bin/bash
DIR=$(dirname $(dirname $(realpath $0)))
TARGET=$(dirname $DIR)/s9e.github.com/TextFormatter/coverage

cd $DIR/tests

rm -f $DIR/docs/coverage/*
phpunit -d memory_limit=256M -c $DIR/tests/phpunit.xml --coverage-html $TARGET

REGEXP=s/`echo $(dirname $(dirname $DIR)) | sed -e 's/\\//\\\\\//g'`//g
sed -i $REGEXP $TARGET/*.html