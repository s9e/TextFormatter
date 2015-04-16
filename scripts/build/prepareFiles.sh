#!/bin/bash

cd $(dirname "$0")

# Cache plugins' JavaScript parsers' source inside the plugin's configurator
#php cacheJavaScriptParsers.php

# Patch the sources for current PHP version
php patchSources.php $@

# Optimize the sources if applicable
if [ -n "$OPTIMIZE" ]
then
	php optimizeSources.php
fi
