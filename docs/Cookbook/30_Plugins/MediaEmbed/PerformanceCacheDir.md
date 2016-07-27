## Use a cache to improve scraping performance

In some cases, a simple URL does not provide all the information needed to embed a resource and the external content has to be downloaded, inspected and the information extracted. This only happens once at parsing time, but if you parse the same text multiple times (e.g. when editing a text) you may want to save a local copy of the external content for performance.

You can do so by setting a `cacheDir` value either during configuration or before parsing, whichever you prefer.

If you set up a cache directory, you should periodically empty it or prune the oldest files so that its size does not get out of hand.

### Set the path to a cache dir during configuration

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->registeredVars['cacheDir'] = '/path/to/cache';
```

### Set the path to a cache dir before parsing

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create $parser and $renderer
extract($configurator->finalize());

$parser->registeredVars['cacheDir'] = '/path/to/cache';
```
