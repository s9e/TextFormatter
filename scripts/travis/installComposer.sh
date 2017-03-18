#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = '5.3.3' ]
then
	composer config disable-tls true
	composer config secure-http false
elif [[ "$TRAVIS_PHP_VERSION" == 7.* ]]
then
	composer require --no-interaction phpunit/phpunit:^5.7
fi

if [ -n "$COVERAGE" ]
then
	composer require --no-interaction satooshi/php-coveralls
fi

composer install --no-interaction --ignore-platform-reqs