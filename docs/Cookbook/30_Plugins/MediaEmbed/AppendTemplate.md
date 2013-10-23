## Add a link to the original URL below the embedded content

If you want to display a link to the original page below the embedded content, the `appendTemplate()` method can be called (before adding media sites) to set a template to be displayed after the embedded content.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->appendTemplate(
	'<a href="{@url}"><xsl:value-of select="@url"/></a>'
);
$configurator->MediaEmbed->add('youtube');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[youtube]http://www.youtube.com/watch?v=-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe><a href="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</a>
```
