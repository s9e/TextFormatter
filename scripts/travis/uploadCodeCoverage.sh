#!/bin/bash

if [ ! -f /tmp/clover.xml ]
then
	echo "No code coverage"
	exit
fi

cd $(dirname "$0")
#php fixCloverReport.php /tmp/clover.xml

cd ../..
php /tmp/ocular.phar code-coverage:upload --format=php-clover /tmp/clover.xml

if [ -f vendor/bin/coveralls ]
then
	php vendor/bin/coveralls --exclude-no-stmt -n -v
fi
