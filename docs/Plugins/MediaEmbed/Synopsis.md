<h2>Synopsis</h2>

This plugin allows the user to embed content from allowed sites using a `[media]` BBCode, site-specific BBCodes such as `[youtube]`, or from simply posting a supported URL in plain text.

It is designed to be able to parse any of the following forms:

 * `[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]` *(simplest form)*
 * `[media=youtube]-cEzsCAzTak[/media]` *(from [XenForo's BB Code Media Sites](http://xenforo.com/help/bb-code-media-sites/))*
 * `[youtube]http://youtu.be/watch?v=-cEzsCAzTak[/youtube]` *(from various forum softwares such as [phpBB](https://www.phpbb.com/customise/db/bbcode/youtube/))*
 * `[youtube=http://www.youtube.com/watch?v=-cEzsCAzTak]` *(from [WordPress's YouTube short code](http://en.support.wordpress.com/videos/youtube/))*
 * `[youtube]-cEzsCAzTak[/youtube]` *(from various forum softwares such as [vBulletin](http://www.vbulletin.com/forum/forum/vbulletin-3-8/vbulletin-3-8-questions-problems-and-troubleshooting/vbulletin-quick-tips-and-customizations/204206-how-to-make-a-youtube-bb-code))*
 * `http://www.youtube.com/watch?v=-cEzsCAzTak` *(plain URLs are turned into embedded content)*

Has built-in support for Dailymotion, Facebook, LiveLeak, Twitch, YouTube [and more](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/MediaEmbed/Configurator/sites/).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->createIndividualBBCodes = true;
$configurator->MediaEmbed->add('dailymotion');
$configurator->MediaEmbed->add('facebook');
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$examples = [
	'[media]http://www.dailymotion.com/video/x222z1[/media]',
	'https://www.facebook.com/video/video.php?v=10100658170103643',
	'[youtube]-cEzsCAzTak[/youtube]'
];

foreach ($examples as $text)
{
	$xml  = $parser->parse($text);
	$html = $renderer->render($xml);

	echo $html, "\n";
}
```
```html
<iframe width="560" height="315" src="//www.dailymotion.com/embed/video/x222z1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#10100658170103643" onload="var a=Math.random();window.addEventListener('message',function(b){if(b.data.id==a)style.height=b.data.height+'px'});contentWindow.postMessage('s9e:'+a,src.substr(0,src.indexOf('/',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe>
```

### Configure a site manually

In addition to the sites that are directly available by name, you can define new, custom sites. More examples are available in the [Cookbook](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/README.md).

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'    => 'youtube.com',
		'extract' => "!youtube\\.com/watch\\?v=(?'id'[-0-9A-Z_a-z]+)!",
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
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
```
