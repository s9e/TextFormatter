#!/bin/bash

cd $(dirname "$0")

composer self-update
composer require --no-interaction matthiasmullie/minify
if [ -n "$COVERAGE" ]
then
	composer require --no-interaction satooshi/php-coveralls
fi

mv vendor ../..