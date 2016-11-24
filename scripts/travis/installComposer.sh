#!/bin/bash

cd $(dirname $(dirname ("$0"))

if [ -n "$COVERAGE" ]
then
	composer require --no-interaction satooshi/php-coveralls
fi