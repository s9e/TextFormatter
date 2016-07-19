## Add a link to the original URL below the embedded content

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
<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></div></div><a href="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</a>
```
