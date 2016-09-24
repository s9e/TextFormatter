This plugin converts plain-text video URLs into playable videos. Only URLs starting with `http://` or `https://` and ending with `.mp4, `.ogg` or `.webm` are converted.

## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autovideo');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://example.org/video.mp4';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<video src="http://example.org/video.mp4"></video>
```
