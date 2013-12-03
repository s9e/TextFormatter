#!/bin/bash

cd $(dirname "$0")

# Install Coveralls and XSLCache on 5.5 and disable XDebug on everything else
if [ "$TRAVIS_PHP_VERSION" = "5.5" ]
then
	# We run this script detached in the background. It'll finish while tests are running
	echo "Installing Composer dependencies"
	sh -c "./installComposer.sh 2>&1 &" >/dev/null 2>&1 &

	# We only install/test XSLCache on 5.5 because people who install PECL extensions usually run
	# the current version of PHP
	echo "Installing XSLCache"
	./installXSLCache.sh >/dev/null 2>&1 &
else
	echo "Removing XDebug"
	phpenv config-rm xdebug.ini
fi

# Install Closure Compiler
echo "Installing Closure Compiler"
./installClosureCompiler.sh >/dev/null 2>&1 &

# The cache dir lets the MediaEmbed plugin cache scraped content
mkdir ../../tests/.cache

# Patch the sources for current PHP version
php ../build/patchSources.php

wait