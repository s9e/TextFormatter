## Synopsis

This plugin converts plain-text emails into clickable "mailto:" links.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autoemail');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Email me at user@example.org';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Email me at <a href="mailto:user@example.org">user@example.org</a>
```