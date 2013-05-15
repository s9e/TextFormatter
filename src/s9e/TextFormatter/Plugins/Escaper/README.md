## Synopsis

This plugin defines the backslash character `\\` as an escape character.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Escaper');
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'The emoticon \\:) becomes :)'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
The emoticon :) becomes <img src="happy.png" alt=":)" title="Happy">
```