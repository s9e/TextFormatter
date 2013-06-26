## Add a site manually

To add a site, you'll need to pass as a second argument to `add()` an array that contains at least 3 elements:

  * `host` must be a string (or an array of strings) that is the second-level domain name(s) of the URLs you want to match, e.g. `example.com`
  * `match` must be a string (or an array of strings) that is the regexp(s) used to match the URLs
  * plus at least one of the following:
    * `iframe`: array that contains the `width`, `height` and `src` of the iframe used to display the embedded content *(other attributes such as "allowfullscreen" are automatically added)*
    * `flash`: array that contains the `width`, `height` and `src` of the flash object used to display the embedded content *(will create a pair of boilerplate `<object>` and `<embed>` elements)*
	* `template`: a string that contains a custom template

### How to configure multiple `host` and `match` values

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'   => ['youtube.com', 'youtu.be'],
		'match'  => [
			"!youtube\\.com/watch\\?v=(?'id'[-0-9A-Z_a-z]+)!",
			"!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!"
		],
		'iframe' => [
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

### How to configure the `iframe` renderer

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'   => 'youtube.com',
		'match'  => "!youtube\\.com/watch\\?v=(?'id'[-0-9A-Z_a-z]+)!",
		'iframe' => [
			'width'  => 560,
			'height' => 315,
			'src'    => 'http://www.youtube.com/embed/{@id}'
		]
	]
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>
```

### How to configure the `flash` renderer

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'dailymotion',
	[
		'host'   => 'dailymotion.com',
		'match'  => "!dailymotion\.com/(?:video/|user/[^#]+#video=)(?'id'[A-Za-z0-9]+)!",
		'flash' => [
			'width'  => 560,
			'height' => 315,
			'src'    => 'http://www.dailymotion.com/swf/{@id}'
		]
	]
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[media]http://www.dailymotion.com/video/x222z1[/media]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/x222z1"><param name="allowFullScreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/x222z1" width="560" height="315" allowfullscreen=""></object>
```

### How to configure a custom `template`

By using a custom template, you have access to more options at the price of increased complexity. Generally, it should only be used if the default `iframe` or `flash` method does not work. Below is the reproduction of the default code used for Twitch links, which uses 3 different kinds of links and 2 different players.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'twitch',
	[
		'host'   => 'twitch.tv',
		'match'  => "!twitch\.tv/(?'channel'[A-Za-z0-9]+)(?:/b/(?'archive_id'[0-9]+)|/c/(?'chapter_id'[0-9]+))?!",
		'template' => <<<'EOT'
			<object type="application/x-shockwave-flash"
			        typemustmatch=""
			        width="620"
			        height="378"
			        data="http://www.twitch.tv/widgets/{substring('archl',5-4*boolean(@archive_id|@chapter_id),4)}ive_embed_player.swf"
			>
				<param name="flashvars">
					<xsl:attribute name="value">channel=<xsl:value-of select="@channel"/><xsl:if test="@archive_id">&amp;archive_id=<xsl:value-of select="@archive_id"/></xsl:if><xsl:if test="@chapter_id">&amp;chapter_id=<xsl:value-of select="@chapter_id"/></xsl:if></xsl:attribute>
				</param>
				<embed type="application/x-shockwave-flash"
				       width="620"
				       height="378"
				       src="http://www.twitch.tv/widgets/{substring('archl',5-4*boolean(@archive_id|@chapter_id),4)}ive_embed_player.swf"
				/>
			</object>
EOT
	]
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[media]http://www.twitch.tv/minigolf2000/b/419320018[/media]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/archive_embed_player.swf"><param name="flashvars" value="channel=minigolf2000&amp;archive_id=419320018"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/archive_embed_player.swf"></object>
```
