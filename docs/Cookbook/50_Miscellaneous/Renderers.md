## Rendering engines

s9e\TextFormatter offers multiple renderers: 2 general purpose renderers, plus a special one. In general, you can just use the default setting but in some cases, some renderers may offer better performance. The type of renderer can be during configuration. Multiple renderers can be created based on the same config, and except for the special one, they will produce the same output.

#### XSLT renderer

```php
$configurator->rendering->engine = 'XSLT';
```

This is the default renderer. It uses [PHP's XSL extension](http://php.net/manual/en/book.xsl.php). You don't need to configure it or specify that you want the XSLT since it's the default.

#### PHP renderer

```php
// With no caching (not recommended)
$configurator->rendering->engine = 'PHP';

// With automatic caching to /tmp
$configurator->rendering->setEngine('PHP', '/tmp');
```

This renderer uses plain PHP plus [PHP's DOM extension](http://www.php.net/manual/en/book.dom.php). It uses a special class that is dynamically generated, similar in design to what most PHP templating engine would use. The first time you generate a PHP renderer, its source code is available in `$renderer->source`.

A class name is automatically generated for the class, based on its content. Alternatively, you can specify a class name for the renderer by setting the `className` property.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->rendering->engine = 'PHP';

extract($configurator->finalize());
echo get_class($renderer), "\n";

$configurator->rendering->engine->className = 'MyRenderer';
extract($configurator->finalize());
echo get_class($renderer);
```
```
Renderer_fa607f1ca8cfd78b07fd63e4b5a08adb60ff9f49
MyRenderer
```

This is the fastest renderer if you enable automatic caching and you have an opcode cache.

#### Unformatted renderer

```php
$configurator->rendering->engine = 'Unformatted';
```

This is a special renderer that actually removes formatting from the text. Paragraphs and line breaks are preserved, special HTML characters are escaped but everything else is rendered as plain text. It is meant to be used as a fallback if all other renderers fail.
