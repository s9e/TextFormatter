#!/bin/bash

if [ "$TRAVIS_PHP_VERSION" = "5.5" ]
then
	phpunit --exclude-group none --coverage-clover /tmp/clover.xml
else
	phpunit --exclude-group needs-network
fi