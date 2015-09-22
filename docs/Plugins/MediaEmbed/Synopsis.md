<h2>Synopsis</h2>

This plugin allows the user to embed content from allowed sites using a `[media]` BBCode, site-specific BBCodes such as `[youtube]`, or from simply posting a supported URL in plain text.

It is designed to be able to parse any of the following forms:

 * `[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]`  
   _(simplest form)_
 * `[media=youtube]-cEzsCAzTak[/media]`  
   _(from [XenForo's BB Code Media Sites](http://xenforo.com/help/bb-code-media-sites/))_
 * `[youtube]http://youtu.be/watch?v=-cEzsCAzTak[/youtube]`  
   _(from various forum softwares such as [phpBB](https://www.phpbb.com/customise/db/bbcode/youtube/))_
 * `[youtube=http://www.youtube.com/watch?v=-cEzsCAzTak]`  
   _(from [WordPress's YouTube short code](http://en.support.wordpress.com/videos/youtube/))_
 * `[youtube]-cEzsCAzTak[/youtube]`  
   _(from various forum softwares such as [vBulletin](http://www.vbulletin.com/forum/forum/vbulletin-3-8/vbulletin-3-8-questions-problems-and-troubleshooting/vbulletin-quick-tips-and-customizations/204206-how-to-make-a-youtube-bb-code))_
 * `http://www.youtube.com/watch?v=-cEzsCAzTak`  
   _(plain URLs are turned into embedded content)_

Has built-in support for Dailymotion, Facebook, Instagram, Twitch, Twitter, YouTube [and more](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/MediaEmbed/Configurator/sites/).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;

// We want to create individual BBCodes such as [youtube] in
// addition to the default [media] BBCode
$configurator->MediaEmbed->createIndividualBBCodes = true;

// Add the sites we want to support
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
<div data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/x222z1" style="border:0;height:100%;position:absolute;width:100%"></iframe></div></div>
<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener('message',function(b){if(b.data.id==a)style.height=b.data.height+'px'});contentWindow.postMessage('s9e:'+a,src.substr(0,src.indexOf('/',8)))" scrolling="no" src="//s9e.github.io/iframe/facebook.min.html#10100658170103643" style="border:0;height:360px;max-width:640px;width:100%"></iframe>
<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;position:absolute;width:100%" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe></div></div>
```

### Configure a site manually

In addition to the sites that are directly available by name, you can define new, custom sites. More examples are available in the [Cookbook](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/README.md).

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
<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:560px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="http://www.youtube.com/embed/-cEzsCAzTak" style="border:0;height:100%;position:absolute;width:100%"></iframe></div></div>
```
