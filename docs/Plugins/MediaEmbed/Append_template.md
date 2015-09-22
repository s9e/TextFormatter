<h2>How to add a link to the original URL below the embedded content</h2>

If you want to display a link to the original page below the embedded content, the `appendTemplate()` method can be called (before adding media sites) to set a template to be displayed after the embedded content.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->appendTemplate(
	'<a href="{@url}"><xsl:value-of select="@url"/></a>'
);
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://www.youtube.com/watch?v=-cEzsCAzTak';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;position:absolute;width:100%" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe></div></div><a href="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</a>
```
