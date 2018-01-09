#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = '5.4' ]
then
	composer remove --dev --no-interaction --ignore-platform-reqs s9e/regexp-builder
	composer require --dev --no-interaction "php:^5.4"
fi

if [ -n "$COVERAGE" ]
then
	composer require --no-interaction satooshi/php-coveralls
fi

composer install --no-interaction