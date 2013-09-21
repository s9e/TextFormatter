## Add a site from the supported list

You can look into the list of supported sites in [sites.xml](https://github.com/s9e/TextFormatter/blob/master/src/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/sites.xml).

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add('dailymotion');
$configurator->MediaEmbed->add('facebook');
$configurator->MediaEmbed->add('youtube');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$examples = [
	'[media]http://www.dailymotion.com/video/x222z1[/media]',
	'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
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
<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/x222z1"><param name="allowFullScreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/x222z1" width="560" height="315" allowfullscreen=""></object>
<iframe width="560" height="315" src="https://www.facebook.com/video/embed?video_id=10100658170103643" allowfullscreen=""></iframe>
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>
```
