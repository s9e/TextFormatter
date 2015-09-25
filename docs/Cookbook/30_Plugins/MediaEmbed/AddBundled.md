## Add a site from the supported list

You can look into the list of supported sites in [the sites directory](https://github.com/s9e/TextFormatter/blob/master/src/Plugins/MediaEmbed/Configurator/sites/).

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
<div data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/x222z1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>
<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener('message',function(b){if(b.data.id==a)style.height=b.data.height+'px'});contentWindow.postMessage('s9e:'+a,src.substr(0,src.indexOf('/',8)))" scrolling="no" src="//s9e.github.io/iframe/facebook.min.html#10100658170103643" style="border:0;height:360px;max-width:640px;width:100%"></iframe>
<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe></div></div>
```
