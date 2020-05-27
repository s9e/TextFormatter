This plugin implements a Markdown-like syntax, inspired by modern flavors of Markdown.

A more detailed description of the syntax is available in [Syntax](Syntax.md).


## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Litedown');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[Link text](http://example.org)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<p><a href="http://example.org">Link text</a></p>
```

### Generating slugs from headers

Calling `addHeadersId()` will modify [headers](Syntax.md#headers) to automatically generate an `id` attribute based on the header's text. The `id` value is made of the lowercased ASCII letters and digits of the text, with everything else replaced with a single dash `-` character. If specified, the method's argument will be used as a prefix. The following example uses `user-content-` as a prefix.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Litedown->addHeadersId('user-content-');

extract($configurator->finalize());

$text = "# Header's title\n"
      . "\n"
      . '...';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<h1 id="user-content-header-s-title">Header's title</h1>

<p>...</p>
```
