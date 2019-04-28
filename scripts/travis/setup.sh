#!/bin/bash

cd $(dirname "$0")

# Disable XDebug
echo "Removing XDebug"
phpenv config-rm xdebug.ini

# Install code coverage tools if applicable
if [ -n "$COVERAGE" ]
then
	echo "Installing Scrutinizer"
	sh -c "./installScrutinizer.sh 2>&1 &" >/dev/null 2>&1 &
fi

# Install Composer dependencies after XDebug has been removed
echo "Installing Composer dependencies"
./installComposer.sh 2>&1 &

# Install Closure Compiler
echo "Installing Closure Compiler"
./installClosureCompiler.sh >/dev/null 2>&1 &

# The cache dir lets the MediaEmbed plugin cache scraped content
mkdir ../../tests/.cache

wait

echo "Starting webserver"
php -S localhost:8000 -d "always_populate_raw_post_data=-1" -t ../../tests 2>/dev/null &