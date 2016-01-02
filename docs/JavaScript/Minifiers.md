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

## Meta-minifiers

### Noop

As the name implies, the no-op minifier will preserve the original source as-is. This is the default setting.

### FirstAvailable

The FirstAvailable strategy allows multiple minifiers to be set. They are executed in order and the result of the first successful minification is returned.

In the following example, we set up the ClosureCompilerService minifier to handle minification. If it fails, the ClosureCompilerApplication will be executed. If it fails too, the Noop (no-op) minifier is executed as a fail-safe and the original source is returned.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();

$minifier = $configurator->javascript->setMinifier('FirstAvailable');
$minifier->add('ClosureCompilerService');
$minifier->add('ClosureCompilerApplication')->closureCompilerBin = '/usr/local/bin/compiler.jar';
$minifier->add('Noop');
```
