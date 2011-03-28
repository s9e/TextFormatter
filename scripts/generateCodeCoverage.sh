#!/bin/bash
DIR=$(dirname $(dirname $(realpath $0)))

cd $DIR/tests

rm -f $DIR/docs/coverage/*
phpunit -d memory_limit=256M -c $DIR/tests/phpunit.xml --coverage-html $DIR/docs/coverage

REGEXP=s/`echo $(dirname $(dirname $DIR)) | sed -e 's/\\//\\\\\//g'`//g
sed -i $REGEXP $DIR/docs/coverage/*.html