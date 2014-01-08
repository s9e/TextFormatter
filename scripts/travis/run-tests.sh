#!/bin/bash

if [ "$TRAVIS_PHP_VERSION" = "5.5" ]
then
	phpunit --exclude-group none --coverage-clover /tmp/clover.xml
elif [ "$TRAVIS_PHP_VERSION" = "hhvm" ]
	phpunit --exclude-group needs-network,no-hhvm
else
	phpunit --exclude-group needs-network
fi