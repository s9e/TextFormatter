<h2>How to add a link to the original URL alongside the embedded content</h2>

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->MediaEmbed->add('youtube');

// After all the tags have been configured, add a `url` attribute and modify
// the template accordingly
foreach ($configurator->tags as $tag)
{
	if (strpos($tag->template, 'data-s9e-mediaembed') === false)
	{
		continue;
	}
	$tag->attributes->add('url')->filterChain->append('#url');
	$tag->filterChain->prepend('addMediaUrl')->addParameterByName('parser');
	$tag->template .= '<xsl:if test="@url"><a href="{@url}"><xsl:value-of select="@url"/></a></xsl:if>';
}

function addMediaUrl($tag, $parser);
{
	// Get the position and length of text consumed by this tag, or pair of tags
	$pos = $tag->getPos();
	if ($tag->getEndTag())
	{
		$len = $tag->getEndTag()->getPos() + $tag->getEndTag()->getLen() - $pos;
	}
	else
	{
		$len = $tag->getLen();
	}

	// If the text contains a URL, add it as an attribute
	$text = substr($parser->getText(), $pos, $len);
	if (preg_match('(https?://[^[]++)', $text, $m))
	{
		$tag->setAttribute('url', $m[0]);
	}
}

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
