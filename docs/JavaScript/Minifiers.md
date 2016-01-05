<h2>Minifiers</h2>

Several minifiers are available. Minification can take several seconds so it is recommended to [set up a cache directory](Introduction.md#speed-up-minification-with-a-cache).

### Google Closure Compiler service

This is the best choice for most users. The Closure Compiler service is [hosted by Google](https://developers.google.com/closure/compiler/docs/terms_ui?csw=1) and is accessible via HTTP. The default configuration gives the best results.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();
$configurator->javascript->setMinifier('ClosureCompilerService');
```

### Google Closure Compiler application

Alternatively, the [Google Closure Compiler application](https://developers.google.com/closure/compiler/docs/gettingstarted_app) can be used. This requires PHP to be able to use `exec()` and for the `java` executable and `compiler.jar` to be available locally. Like the Google Closure Compiler service, configuration is automatic.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();
$configurator->javascript
	->setMinifier('ClosureCompilerApplication')
	->closureCompilerBin = '/usr/local/bin/compiler.jar';
```

### MatthiasMullie\\Minify

[Minify](http://www.minifier.org/) is a JavaScript minifier written in PHP. Its minification is not as extensive as Google's Closure Compiler but it is fast and does not use any external service. In order to use this minifier you must have Minify already installed.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();
$configurator->javascript->setMinifier('MatthiasMullieMinify');
```

### Hosted minifier

Experimental minifier that uses a [remote webservice](https://github.com/s9e/WebService-Minifier). Currently only useful if you want to host your own webservice and you minify the same configuration over multiple installations.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();
$configurator->javascript
	->setMinifier('HostedMinifier')
	->url = 'http://example.org/path/to/minifier/minify.php';
```

### Remote cache

Experimental minifier that only works when paired with a [hosted minifier](#hosted-minifier). It accesses the hosted minifier's cache directly. It saves some network activity in case of a hit but wastes a roundtrip on a miss, that's why it's only useful when minifying the the same configuration over multiple installations.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();

$minifier = $configurator->javascript->setMinifier('FirstAvailable');

$minifier->add('RemoteCache')->url    = 'http://example.org/path/to/minifier/cache/';
$minifier->add('HostedMinifier')->url = 'http://example.org/path/to/minifier/minify.php';
```

## Meta-minifiers

### Noop

As the name implies, the no-op minifier will preserve the original source as-is. This is the default setting.

### FirstAvailable

The FirstAvailable strategy allows multiple minifiers to be set. They are executed in order and the result of the first successful minification is returned.

In the following example, we set up the ClosureCompilerService minifier to handle minification. If it fails, the MatthiasMullieMinify minifier will be executed. If it fails too, the Noop (no-op) minifier is executed as a fail-safe and the original source is returned.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();

$minifier = $configurator->javascript->setMinifier('FirstAvailable');
$minifier->add('ClosureCompilerService');
$minifier->add('MatthiasMullieMinify');
$minifier->add('Noop');
```
