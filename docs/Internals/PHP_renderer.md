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
Default: Renderer_81cb6fc33d8d760fec2542da3af40fe90322085a
Custom:  MyRenderer

Last class: MyRenderer
Last file:  /tmp/MyRenderer.php
```
