This plugin implements a Markdown-like syntax, inspired by modern flavors of Markdown.

A more detailed description of the syntax in available in [Syntax](Syntax.md).

## Example

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
