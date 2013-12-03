#!/bin/bash

cd $(dirname $(dirname $(dirname "$0")))
composer install --dev -q --no-interaction