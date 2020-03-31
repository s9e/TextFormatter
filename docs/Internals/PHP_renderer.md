In addition to the default XSLT renderer, it is possible to compile templates to native PHP. You will need a writable directory to save the compiled template as a PHP file.

```php
$configurator->rendering->engine = 'PHP';
$configurator->rendering->engine->cacheDir = '/path/to/dir';
```

A class name is automatically generated for the renderer class, based on its content. Alternatively, you can specify a class name by setting the `className` property. The class name and file name of the last generated renderer instance can be accessed via the renderer generator.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->rendering->engine           = 'PHP';
$configurator->rendering->engine->cacheDir = '/tmp';

extract($configurator->finalize());
echo 'Default: ', get_class($renderer), "\n";

$configurator->rendering->engine->className = 'MyRenderer';
extract($configurator->finalize());
echo 'Custom:  ', get_class($renderer), "\n\n";

echo 'Last class: ', $configurator->rendering->engine->lastClassName, "\n";
echo 'Last file:  ', $configurator->rendering->engine->lastFilepath;
```
```
Default: Renderer_42d42900e1fe70a3dfadc1c19ebcd948e925d8ba
Custom:  MyRenderer

Last class: MyRenderer
Last file:  /tmp/MyRenderer.php
```


### PHP renderer limitations

Not every XSL element and XPath function is supported by the PHP renderer. One way to detect unsupported templates early in the configuration is to enable the `DisallowUncompilableXSL` template check.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add the DisallowUncompilableXSL check
$configurator->templateChecker->append('DisallowUncompilableXSL');

// Create a BBCode with an unsupported element
try
{
	$configurator->BBCodes->addCustom('[x]', '<xsl:processing-instruction name="foo"/>');
}
catch (RuntimeException $e)
{
	echo get_class($e), ': ', $e->getMessage();
}
```
```
RuntimeException: xsl:processing-instruction elements are not supported
```
