## Synopsis

By default, s9e\TextFormatter escapes HTML entities. This plugins allows HTML entities to be used.

Note: while numeric entities such as `&#160;` are always available, the list of named entities such as `&hearts;` depends on PHP's internal table.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('HTMLEntities');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'I &hearts; HTML.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
I ♥ HTML.
```