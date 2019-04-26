#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ -n "$COVERAGE" ]
then
	composer require --no-interaction php-coveralls/php-coveralls
else
	composer install --no-interaction
fi
