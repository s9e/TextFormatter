Emoji are a standardized set of pictographs.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world ☺';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="☺" class="Emoji twitter-emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/263a.png">
```