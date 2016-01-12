#!/bin/bash

PACKAGES=matthiasmullie/minify
if [ -n "$COVERAGE" ]
then
	PACKAGES="$PACKAGES satooshi/php-coveralls"
fi

cd $(dirname "$0")

composer self-update
composer require --no-interaction "$PACKAGES"

mv vendor ../..