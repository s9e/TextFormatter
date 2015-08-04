#!/bin/bash

cd $(dirname "$0")
cd ../..

#composer install --dev -q --no-interaction
composer self-update
composer require --no-interaction "satooshi/php-coveralls:*"
