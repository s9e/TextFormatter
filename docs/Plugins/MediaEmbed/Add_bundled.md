<h2>How to add a site from the supported list</h2>

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
