#!/bin/bash

cd $(dirname "$0")

# Cache plugins' JavaScript parsers' source inside the plugin's configurator
#php cacheJavaScriptParsers.php

# Cache JavaScript functions' source
php cacheJavaScriptFunctions.php

# Remove the MediaEmbed default site files
rm -f ../../src/Plugins/MediaEmbed/Configurator/sites/*
rmdir ../../src/Plugins/MediaEmbed/Configurator/sites

# Patch the sources for current PHP version
php patchSources.php $@

# Optimize the sources if applicable
if [ -n "$OPTIMIZE" ]
then
	php optimizeSources.php
fi

# Coalesce the Configurator files
php coalesceConfiguratorFiles.php