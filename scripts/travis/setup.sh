#!/bin/bash

cd $(dirname "$0")

# Install Coveralls on 5.5 and disable XDebug on everything else
if [ "$TRAVIS_PHP_VERSION" = "5.5" ]
then
	# We run this script detached in the background. It'll finish while tests are running
	sh -c "./installComposer.sh 2>&1 &" >/dev/null 2>&1 &
else
	phpenv config-rm xdebug.ini
fi

# Install optional dependencies
./installClosureCompiler.sh >/dev/null 2>&1 &
./installXSLCache.sh        >/dev/null 2>&1 &

# The cache dir lets the MediaEmbed plugin cache scraped content
mkdir ../../tests/.cache

# Patch the sources for current PHP version
php ../build/patchSources.php

wait