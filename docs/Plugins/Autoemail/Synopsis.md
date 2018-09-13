This plugin converts plain-text email addresses into clickable "mailto:" links.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autoemail');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Email me at user@example.org';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Email me at <a href="mailto:user@example.org">user@example.org</a>
```
