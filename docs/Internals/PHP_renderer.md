In addition to the default XSLT renderer, it is possible to compile templates to native PHP. You will need a writable directory to save the compiled template as a PHP file.

```php
$configurator->rendering->engine = 'PHP';
$configurator->rendering->engine->cacheDir = '/path/to/dir';
```

The first time a PHP renderer is generated, its source code is available in `$renderer->source`.

A class name is automatically generated for the renderer class, based on its content. Alternatively, you can specify a class name by setting the `className` property.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->rendering->engine = 'PHP';

extract($configurator->finalize());
echo 'Default: ', get_class($renderer), "\n";

$configurator->rendering->engine->className = 'MyRenderer';
extract($configurator->finalize());
echo 'Custom:  ', get_class($renderer);
```
```
Default: Renderer_f7c915b7d80bd1c718b3de66bbf2b51003afe7a6
Custom:  MyRenderer
```
