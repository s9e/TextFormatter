#!/bin/bash

cd $(dirname "$0")
cd ../..

#composer install --dev -q --no-interaction
composer require --dev -q --no-interaction "satooshi/php-coveralls:*"
