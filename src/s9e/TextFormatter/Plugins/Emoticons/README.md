## Synopsis

This plugin performs simple replacements, best suited for handling emoticons.
Matching is case-sensitive.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Hello world :)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img src="happy.png" alt=":)" title="Happy">
```