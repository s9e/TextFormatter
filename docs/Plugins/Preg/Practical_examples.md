### Capture Spotify URIs

In the following example, we use simple pattern matching to capture the content of a Spotify URI and combine it with the MediaEmbed plugin.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->MediaEmbed->add('spotify');
$configurator->Preg->match('/spotify:(?<path>\\S+)/', 'SPOTIFY');

extract($configurator->finalize());

$text = 'spotify:user:erebore:playlist:788MOXyTfcUb1tdw4oC7KJ';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
spotify:user:erebore:playlist:788MOXyTfcUb1tdw4oC7KJ
```
