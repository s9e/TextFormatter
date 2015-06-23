<h2>How to use responsive embeds</h2>

Most embeds can be made responsive if you call `enableResponsiveEmbeds()` before adding sites. This will wrap the embedded content in a `div` element that will automatically shrink if the original content is wider than the available space.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->MediaEmbed->enableResponsiveEmbeds();
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'https://youtu.be/-cEzsCAzTak';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n";
```
```html
<div style="display:inline-block;width:100%;max-width:560px"><div style="height:0;position:relative;padding-top:56.25%"><iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" style="position:absolute;top:0;left:0;width:100%;height:100%" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe></div></div>
```
