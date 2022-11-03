### Capture Spotify URIs

In the following example, we use simple pattern matching to capture the content of a Spotify URI and combine it with the MediaEmbed plugin.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->MediaEmbed->add('spotify');
$configurator->Preg->match('/spotify:(?<id>\\S+)/', 'SPOTIFY');

extract($configurator->finalize());

$text = 'spotify:user:erebore:playlist:788MOXyTfcUb1tdw4oC7KJ';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe data-s9e-mediaembed="spotify" allow="encrypted-media" allowfullscreen="" loading="lazy" scrolling="no" src="https://open.spotify.com/embed/user/erebore/playlist/788MOXyTfcUb1tdw4oC7KJ" style="border:0;border-radius:12px;height:380px;max-width:900px;width:100%"></iframe>
```
