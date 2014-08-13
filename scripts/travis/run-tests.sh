#!/bin/bash

if [ -n "$COVERAGE" ]
then
	phpunit --exclude-group needs-js --coverage-clover /tmp/clover.xml
elif [ "$TRAVIS_PHP_VERSION" = "hhvm" ]
then
	phpunit --exclude-group needs-network,no-hhvm
else
	phpunit --exclude-group needs-network
fi