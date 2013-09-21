## Synopsis

This plugin implements a Markdown-like syntax, inspired by modern flavors of Markdown.

**Work in progress, not currently usable.**

## Syntax

```
[inline url text](http://example.org)
```

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('MarkdownLite');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[inline url text](http://example.org)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://example.org">inline url text</a>
```
