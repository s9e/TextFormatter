<h2>How to add a link to the original URL below the embedded content</h2>

One way to display a link to the original URL used to the embed content is to create a [template normalizer](../../Templating/Template_normalization/Change_default.md#add-your-own-custom-normalization) before adding any media sites.

```php
function appendMediaLink($root)
{
	// Check that the first element has a data-s9e-mediaembed attribute
	$xpath = new DOMXPath($root->ownerDocument);
	$nodes = $xpath->query('*[@data-s9e-mediaembed]');
	if (!$nodes->length)
	{
		return;
	}

	// Append our custom XSL to this template
	$fragment = $root->ownerDocument->createDocumentFragment();
	$fragment->appendXML(
		'<xsl:if test="@url" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
			<a href="{@url}"><xsl:value-of select="@url"/></a>
		</xsl:if>'
	);

	$root->appendChild($fragment);
}

$configurator = new s9e\TextFormatter\Configurator;
$configurator->templateNormalizer->add('appendMediaLink')->onlyOnce = true;
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://www.youtube.com/watch?v=-cEzsCAzTak';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span><a href="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</a>
```
