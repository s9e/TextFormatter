## Synopsis

This plugin allows the user to embed content from allowed sites using a `[media]` BBCode, site-specific BBCodes such as `[youtube]`, or from simply posting a supported URL in plain text.

It is designed to be able to parse any of the following forms:

 * `[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]` *(simplest form)*
 * `[media=youtube]-cEzsCAzTak[/media]` *(from [XenForo's BB Code Media Sites](http://xenforo.com/help/bb-code-media-sites/))*
 * `[youtube]http://youtu.be/watch?v=-cEzsCAzTak[/youtube]` *(from various forum softwares such as [phpBB](https://www.phpbb.com/customise/db/bbcode/youtube/))*
 * `[youtube=http://www.youtube.com/watch?v=-cEzsCAzTak]` *(from [WordPress's YouTube short code](http://en.support.wordpress.com/videos/youtube/))*
 * `[youtube]-cEzsCAzTak[/youtube]` *(from various forum softwares such as [vBulletin](http://www.vbulletin.com/forum/forum/vbulletin-3-8/vbulletin-3-8-questions-problems-and-troubleshooting/vbulletin-quick-tips-and-customizations/204206-how-to-make-a-youtube-bb-code))*
 * `http://www.youtube.com/watch?v=-cEzsCAzTak` *(plain URLs are turned into embedded content)*

Has built-in support for Dailymotion, Facebook, LiveLeak, Twitch, YouTube [and more](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/sites.xml).

## Example

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

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[youtube]http://www.youtube.com/watch?v=-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>
```

### Supported sites

<table>
	<tr>
		<th>Id</th>
		<th>Site</th>
		<th>Example URLs</th>
	</tr>
	<tr>
		<td><code>break</code></td>
		<td>Break</td>
		<td>http://www.break.com/video/video-game-playing-frog-wants-more-2278131</td>
	</tr>
	<tr>
		<td><code>collegehumor</code></td>
		<td>CollegeHumor</td>
		<td>http://www.collegehumor.com/video/1181601/more-than-friends</td>
	</tr>
	<tr>
		<td><code>dailymotion</code></td>
		<td>Dailymotion</td>
		<td>http://www.dailymotion.com/video/x222z1<br/>http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</td>
	</tr>
	<tr>
		<td><code>facebook</code></td>
		<td>Facebook</td>
		<td>https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater</td>
	</tr>
	<tr>
		<td><code>funnyordie</code></td>
		<td>Funny or Die</td>
		<td>http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david</td>
	</tr>
	<tr>
		<td><code>gist</code></td>
		<td>GitHub Gist</td>
		<td>https://gist.github.com/s9e/6806305</td>
	</tr>
	<tr>
		<td><code>liveleak</code></td>
		<td>LiveLeak</td>
		<td>http://www.liveleak.com/view?i=3dd_1366238099</td>
	</tr>
	<tr>
		<td><code>metacafe</code></td>
		<td>Metacafe</td>
		<td>http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</td>
	</tr>
	<tr>
		<td><code>slideshare</code></td>
		<td>SlideShare</td>
		<td>http://www.slideshare.net/Slideshare/how-23431564</td>
	</tr>
	<tr>
		<td><code>soundcloud</code></td>
		<td>SoundCloud</td>
		<td>http://api.soundcloud.com/tracks/98282116</td>
	</tr>
	<tr>
		<td><code>ted</code></td>
		<td>TED Talks</td>
		<td>http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html<br/>http://embed.ted.com/playlists/26/our_digital_lives.html</td>
	</tr>
	<tr>
		<td><code>twitch</code></td>
		<td>Twitch</td>
		<td>http://www.twitch.tv/minigolf2000<br/>http://www.twitch.tv/minigolf2000/c/2475925<br/>http://www.twitch.tv/minigolf2000/b/361358487</td>
	</tr>
	<tr>
		<td><code>vimeo</code></td>
		<td>Vimeo</td>
		<td>http://vimeo.com/67207222<br/>http://vimeo.com/channels/staffpicks/67207222</td>
	</tr>
	<tr>
		<td><code>wshh</code></td>
		<td>WorldStarHipHop</td>
		<td>http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0<br/>http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61</td>
	</tr>
	<tr>
		<td><code>youtube</code></td>
		<td>YouTube</td>
		<td>http://www.youtube.com/watch?v=-cEzsCAzTak<br/>http://youtu.be/-cEzsCAzTak</td>
	</tr>
</table>