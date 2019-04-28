#!/bin/bash

cd $(dirname "$0")
cd ../..

CMD=./vendor/bin/phpunit

if [ -n "$COVERAGE" ]
then
	# Run the network tests in parallel to populate the cache
	CACHE_PRELOAD=1 $CMD --group needs-network tests/Plugins/MediaEmbed/ParserTest.php > /dev/null &

	phpdbg -qrr $CMD --exclude-group needs-js --coverage-clover /tmp/clover.xml
else
	$CMD --exclude-group needs-network
fi