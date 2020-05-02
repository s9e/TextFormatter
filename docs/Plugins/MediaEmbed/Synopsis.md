<h2>Synopsis</h2>

This plugin allows the user to embed content from allowed sites using a `[media]` BBCode or by simply posting a supported URL in plain text. It is designed to be able to parse any of the following forms:

 * `[media]https://youtu.be/-cEzsCAzTak[/media]`
 * `https://www.youtube.com/watch?v=-cEzsCAzTak`

[Other kind of markup](Other_markup.md) can be configured manually.

It has built-in support for Facebook, Twitch, Twitter, YouTube [and many more](Sites.md).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;

// We want to create an individual BBCode for [youtube] in
// addition to the default [media] BBCode
$configurator->BBCodes->add(
	'youtube',
	['defaultAttribute' => 'url', 'contentAttributes' => ['url']]
);

// Add the sites we want to support
$configurator->MediaEmbed->add('facebook');
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$examples = [
	'[media]https://youtu.be/-cEzsCAzTak[/media]',
	'https://www.facebook.com/video/video.php?v=10100658170103643'
];

foreach ($examples as $text)
{
	$xml  = $parser->parse($text);
	$html = $renderer->render($xml);

	echo $html, "\n";
}
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/-cEzsCAzTak/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/-cEzsCAzTak"></iframe></span></span>
<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var c=new MessageChannel;c.port1.onmessage=function(e){style.height=e.data+'px'};contentWindow.postMessage('s9e:init','https://s9e.github.io',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/facebook.min.html#v10100658170103643" style="border:0;height:360px;max-width:640px;width:100%"></iframe>
```

### Configure a site manually

In addition to the sites that are directly available by name, you can define new, custom sites.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'    => 'youtube.com',
		'extract' => "!youtube\\.com/watch\\?v=(?'id'[-\\w]+)!",
		'iframe'  => [
			'width'  => 560,
			'height' => 315,
			'src'    => 'http://www.youtube.com/embed/{@id}'
		]
	]
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://www.youtube.com/watch?v=-cEzsCAzTak';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:560px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="http://www.youtube.com/embed/-cEzsCAzTak" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>
```
