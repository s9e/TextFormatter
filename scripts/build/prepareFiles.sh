#!/bin/bash

cd $(dirname "$0")

# Update the PHP requirement in composer.json and remove the post-install script
php patchComposer.php "$@"

# Cache plugins' JavaScript parsers' source inside the plugin's configurator
#php cacheJavaScriptParsers.php

# Cache JavaScript functions' source
php cacheJavaScriptFunctions.php

# Remove the MediaEmbed default site files
rm -f ../../src/Plugins/MediaEmbed/Configurator/sites/*
rmdir ../../src/Plugins/MediaEmbed/Configurator/sites

# Patch the sources for current PHP version
php patchSources.php "$@"

# Optimize the sources if we do not generate a code coverage report
if [ -z "$COVERAGE" ]
then
	php optimizeSources.php
fi

# Coalesce the Configurator files
php coalesceConfiguratorFiles.php