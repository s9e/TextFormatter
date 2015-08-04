#!/bin/bash

cd $(dirname "$0")

echo "Disabling Zend GC"
echo "zend.enable_gc=0" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

# Install Coveralls if we're saving code coverage and disable XDebug otherwise
if [ -n "$COVERAGE" ]
then
	echo "Installing Composer dependencies"
	./installComposer.sh 2>&1 &

	# Install Scrutinizer's external code coverage tool
	echo "Installing Scrutinizer"
	./installScrutinizer.sh >/dev/null 2>&1 &
else
	echo "Removing XDebug"
	phpenv config-rm xdebug.ini
fi

# Install Closure Compiler
echo "Installing Closure Compiler"
./installClosureCompiler.sh >/dev/null 2>&1 &

# The cache dir lets the MediaEmbed plugin cache scraped content
mkdir ../../tests/.cache

# Prepare the files as for a release branch
../build/prepareFiles.sh

wait