## Add a site manually

To add a site, you'll need to pass as a second argument to `add()` an array that contains at least 3 elements:

  * `host` is the second-level domain name of the URLs you want to match, e.g. `example.com`
  * at least one of the following:
    * `extract` is a regexp used to extract values from the URL
    * `scrape` is an array that must contain at least one of each:
      * `match` is a regexp used to determine whether to scrape the content of the URL
      * `extract` is a regexp used to extract values from the scraped page
  * plus at least one of the following:
    * `iframe`: array that contains the `width`, `height` and `src` of the iframe used to display the embedded content *(other attributes such as "allowfullscreen" are automatically added)*
    * `flash`: array that contains the `width`, `height` and `src` of the flash object used to display the embedded content *(will create a pair of boilerplate `<object>` and `<embed>` elements)*
    * `template`: a string that contains a custom template

You can specify multiple `host`, `scrape`, `extract` or `match` values using arrays.

### How to configure multiple `host` and `extract` values

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'    => ['youtube.com', 'youtu.be'],
		'extract' => [
			"!youtube\\.com/watch\\?v=(?'id'[-0-9A-Z_a-z]+)!",
			"!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!"
		],
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
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
```

### How to configure the `iframe` renderer

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

$text = '[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
```

### How to configure the `flash` renderer

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'dailymotion',
	[
		'host'    => 'dailymotion.com',
		'extract' => "!dailymotion\.com/(?:video/|user/[^#]+#video=)(?'id'[A-Za-z0-9]+)!",
		'flash'   => [
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
<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/x222z1"><param name="allowfullscreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/x222z1" width="560" height="315" allowfullscreen=""></object>
```

### How to configure a custom `template`

By using a custom template, you have access to more options at the price of increased complexity. Generally, it should only be used if the default `iframe` or `flash` method does not work. Below is the reproduction of the default code used for Twitch links, which uses 3 different kinds of links and 2 different players.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'twitch',
	[
		'host'     => 'twitch.tv',
		'extract'  => "!twitch\.tv/(?'channel'[A-Za-z0-9]+)(?:/b/(?'archive_id'[0-9]+)|/c/(?'chapter_id'[0-9]+))?!",
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

### How to scrape content

Some media sites don't put all of the necessary data (e.g. the ID of a video) in the URL. In that case, you may have to retrieve it from the page itself.

Note that scraping content is a pretty expensive operation that can take several seconds to complete, in part due to network latency and the responsiveness of the target site.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'slideshare',
	[
		'host'   => 'slideshare.net',
		'scrape' => [
			// Here we ensure that we don't scrape just every link to http://slideshare.net
			'match'   => '!slideshare\\.net/[^/]+/\\w!',
			// Retrieve the presentationId from the embedded JSON
			'extract' => '!"presentationId":(?<id>[0-9]+)!'
		],
		'iframe' => [
			'width'  => 427,
			'height' => 356,
			'src'    => 'http://www.slideshare.net/slideshow/embed_code/{@id}'
		]
	]
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="427" height="356" src="http://www.slideshare.net/slideshow/embed_code/21112125" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
```

### Specify a different URL for scraping

If the URL used for scraping is different from the media's URL, you can specify it in the `url` element of the `scrape` array. You can also use variables in the URL using the familiar syntax `{@id}`. Values for those variables come from named captures in previous `extract` regexp and from the tag's attributes if applicable.

For example: in some places, Grooveshark uses hashbang URLs such as `http://grooveshark.com/#!/s/Soul+Below/4zGL7i`. Fragment identifiers (everything after `#`) are by definition omitted when requesting the page. If we tried to retrieve this URL as-is, the server would return the page that corresponds to `http://grooveshark.com/`.

In the following example, we configure `scrape` with a custom URL by matching everything after the hashbang using a capture named `path` and reconstructing the URL without the hashbang.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'grooveshark',
	[
		'host'   => 'grooveshark.com',
		'scrape' => [
			'match'   => "%grooveshark\\.com(?:/#!?)?/s/(?'path'[^/]+/.+)%",
			'url'     => 'http://grooveshark.com/s/{@path}',
			'extract' => "%songID=(?'songid'[0-9]+)%"
		],
		'flash' => [
			'width'     => 250,
			'height'    => 40,
			'src'       => 'http://grooveshark.com/songWidget.swf',
			'flashvars' => 'songID={@songid}'
		]
	]
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'http://grooveshark.com/#!/s/Soul+Below/4zGL7i';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<object type="application/x-shockwave-flash" typemustmatch="" width="250" height="40" data="http://grooveshark.com/songWidget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="songID=35292216"><embed type="application/x-shockwave-flash" src="http://grooveshark.com/songWidget.swf" width="250" height="40" allowfullscreen="" flashvars="songID=35292216"></object>
```
