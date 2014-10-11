#!/bin/bash

if [ -n "$COVERAGE" ]
then
	phpunit --exclude-group needs-js --coverage-clover /tmp/clover.xml
else
	phpunit --exclude-group needs-network
fi