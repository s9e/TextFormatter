#!/bin/bash
DIR=$(dirname $(dirname $(realpath $0)))

rm -f $DIR/docs/coverage/*
phpunit -d memory_limit=256M -c $DIR/tests/phpunit.xml --coverage-html $DIR/docs/coverage $DIR/tests

REGEXP=s/`echo $(dirname $(dirname $DIR)) | sed -e 's/\\//\\\\\//g'`//g
sed -i $REGEXP $DIR/docs/coverage/*.html