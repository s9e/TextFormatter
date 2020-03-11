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
<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:320px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:125%;padding-bottom:calc(100% + 80px)"><iframe allow="encrypted-media" allowfullscreen="" scrolling="no" src="https://open.spotify.com/embed/user/erebore/playlist/788MOXyTfcUb1tdw4oC7KJ" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>
```
