## Renderers

s9e\TextFormatter offers multiple renderers: 3 general purpose renderers, plus a special one. In general, you can just use the default setting but in some cases, some renderers may offer better performance. The type of renderer can be during configuration. Multiple renderers can be created based on the same config, and except for the special one, they will produce the same output.

### How to create different renderers

If you call `$configurator->getRenderer()` you'll get an instance of the default renderer. If you want a specific renderer, you can specify its name and options.

#### XSLT renderer

`$configurator->getRenderer('XSLT')` will return an instance of the XSLT renderer, no configuration required. It uses [PHP's default XSL extension](http://php.net/manual/en/book.xsl.php).

#### PHP renderer

`$configurator->getRenderer('PHP')` will return an instance of the PHP renderer. The PHP renderer is a special class that is dynamically generated, similar in design to what most PHP templating engine would use. The first time you generate a PHP renderer, its source code is available in `$renderer->source`. Obviously, you need to save this source code if you wish to reuse the renderer.

You can specify a class name for the renderer, and path where to save it by passing them as such:
```php
$configurator->getRenderer('PHP', 'MyRenderer', '/path/to/MyRenderer.php');
```
This will automatically create a `MyRenderer` class and save it to the specified path. If you don't specify a path, no file will be saved. If you don't specify a class name, a random name will be generated. The class name can be namespaced if you want, e.g.
```php
$configurator->getRenderer('PHP', 'My\Renderer', '/path/to/My/Renderer.php');
```

#### XSLCache renderer

`$configurator->getRenderer('XSLCache', '/path/to/cache')` will return an instance of the XSLCache renderer and create an XSL file in the `/path/to/cache` directory. It uses [PECL's xslcache extension](http://pecl.php.net/package/xslcache).

#### Unformatted renderer

`$configurator->getRenderer('Unformatted')` will return an instance of the Unformatted renderer. This renderer is special, as it removes formatting from the text. Paragraphs and line breaks are preserved, special HTML characters are escaped but everything else is rendered as plain text.

### How to change the default renderer

You can change the default renderer in the configurator, using the same syntax as presented above:

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->setRendererGenerator('PHP', 'MyRenderer', '/path/to/MyRenderer.php');

// Will return an instance of MyRenderer (the PHP renderer) and save it to /path/to/MyRenderer.php
$configurator->getRenderer();
```
