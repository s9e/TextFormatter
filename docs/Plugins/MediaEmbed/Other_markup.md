<h2>Support other markup</h2>

### Specialized BBCode

In the following example, we create a `[youtube]` BBCode that accepts the ID of a YouTube video and displays it as if it was posted via the MediaEmbed plugin.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add('youtube');
$configurator->BBCodes->add('youtube', ['contentAttributes' => ['id']]);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[youtube]-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>
```

### Generic BBCode

In the following example, we create a generic `[video]` BBCode that accepts a single URL and processes it through the MediaEmbed plugin.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add('youtube');
$configurator->BBCodes->add(
	'video',
	[
		'contentAttributes' => ['url'],
		'tagName'           => 'MEDIA'
	]
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[video]http://www.youtube.com/watch?v=-cEzsCAzTak[/video]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>
```

### XenForo-style BBCode

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add('youtube');
$configurator->BBCodes['MEDIA']->defaultAttribute = 'site';
$configurator->tags['MEDIA']->filterChain
	->prepend('handleXenForoTag')
	->resetParameters()
	->addParameterByName('tag')
	->addParameterByName('parser');

function handleXenForoTag($tag, $parser)
{
	if (!$tag->hasAttribute('site') || !$tag->hasAttribute('url'))
	{
		return;
	}
	$tag->invalidate();

	$tagPos = $tag->getPos();
	if ($tag->getEndTag())
	{
		$tagLen = $tag->getEndTag()->getPos() + $tag->getEndTag()->getLen() - $tagPos;
	}
	else
	{
		$tagLen = $tag->getLen();
	}

	$tagName = strtoupper($tag->getAttribute('site'));
	$parser->addSelfClosingTag($tagName, $tagPos, $tagLen)
	       ->setAttribute('id', $tag->getAttribute('url'));
}

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[media=youtube]-cEzsCAzTak[/media]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>
```
