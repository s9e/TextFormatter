#!/bin/bash

cd $(dirname "$0")

if [ "$TRAVIS_PHP_VERSION" != 'hhvm' ]
then
	echo "Disabling Zend GC"
	echo "zend.enable_gc=0" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi

# Install code coverage tools if applicable and disable XDebug otherwise
if [ -n "$COVERAGE" ]
then
	# Install Scrutinizer's external code coverage tool
	echo "Installing Scrutinizer"
	sh -c "./installScrutinizer.sh 2>&1 &" >/dev/null 2>&1 &
else
	echo "Removing XDebug"
	phpenv config-rm xdebug.ini
fi

# Install Composer dependencies after XDebug has been removed
echo "Installing Composer dependencies"
./installComposer.sh 2>&1 &

# Install Closure Compiler
echo "Installing Closure Compiler"
./installClosureCompiler.sh >/dev/null 2>&1 &

# The cache dir lets the MediaEmbed plugin cache scraped content
mkdir ../../tests/.cache

# Prepare the files as for a release branch
../build/prepareFiles.sh

wait

# Start a local webserver for the Http helper's tests
if [ "$TRAVIS_PHP_VERSION" != '5.3.3' ]
then
	php -S localhost:80 -t ../../tests &
fi
