<h2>vBulletin5, a bundle for reading content from vBulletin 5</h2>


### Example

```php
use s9e\TextFormatter\Bundles\vBulletin5 as TextFormatter;

$text = '[b]Rich[/b] text.';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html;
```
```html
<b>Rich</b> text.
```


### Plugins

 * Autoemail
 * Autolink
 * BBCodes
 * Emoji
 * Emoticons
 * MediaEmbed


### BBCodes

BBCodes enabled: `*`, `attach`, `b`, `center`, `code`, `color`, `email`, `font`, `html` (as an alias for `code`), `i`, `img`, `indent`, `left`, `list`, `media`, `node`, `noparse`, `php` (as an alias for `code`), `quote`, `right`, `size`, `table`, `td`, `tr`, `u`, `url`, `user`, and `video` (as an alias for `media`).


### Emoji and Emoticons

Emoji are supported thanks to the free image set from [Twemoji](https://twemoji.twitter.com/). Please consult their website for [license terms](https://twemoji.twitter.com/).

The following emoticons are also supported as an alias to the corresponding emoji: `:)`, `:(`, `;)`, `:D`, `:mad:`, `:cool:`, `:p`, `:o`, `:rolleyes:`, `:eek:`, and `:confused:`.


### Parameters

```php
use s9e\TextFormatter\Bundles\vBulletin5 as TextFormatter;

$text = '[quote="John Doe;1"]...[/quote]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml, [
	// Root URL for nodes
	'NODE_URL' => '/path/to/node/'
]);

echo $html;
```
```html
<blockquote cite="/path/to/node/1"><div><cite>John Doe wrote:</cite>...</div></blockquote>
```
