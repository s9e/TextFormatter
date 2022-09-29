<h2>Forum, a bundle for forum software</h2>

### Example

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = '[quote="John Doe"]Star Wars spoiler: [spoiler]Snapes kills Dumbledore[/spoiler][/quote]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html;
```
```html
<blockquote><div><cite>John Doe wrote:</cite>Star Wars spoiler: <details class="spoiler"><summary class="spoiler-header" data-hide="Hide" data-show="Show">Spoiler </summary><div class="spoiler-content">Snapes kills Dumbledore</div></details></div></blockquote>
```

### Plugins

 * Autoemail
 * Autolink
 * BBCodes
 * Emoji
 * Emoticons
 * MediaEmbed

### BBCodes

BBCodes enabled: b, center, code, color, email, i, img, list, li, *, media, quote, s, size, spoiler, u, url.
Media BBCodes: Bandcamp, Dailymotion, Facebook, Indiegogo, Instagram, Kickstarter, Liveleak, Soundcloud, Twitch, Twitter, Vimeo, Vine, WSHH, YouTube.

### Emoji and Emoticons

Emoji are supported thanks to the free image set from [Twemoji](https://twemoji.twitter.com/). Please consult their website for [license terms](https://twemoji.twitter.com/).

The following emoticons are also supported as an alias to the corresponding emoji: `:)` `:-)` `;)` `;-)` `:D` `:-D` `:(` `:-(` `:-*` `:P` `:-P` `:p` `:-p` `;P` `;-P` `;p` `;-p` `:?` `:-?` `:|` `:-|` `:o` `:lol:`

### Parameters

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = '[quote="John Doe"]Star Wars spoiler :) [spoiler]Spocks kills Dumbledore[/spoiler][/quote]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml, [
	// Translatable strings used in [quote] and [spoiler]
	'L_WROTE'   => 'escribió:',
	'L_HIDE'    => 'Ocultar',
	'L_SHOW'    => 'Mostrar',
	'L_SPOILER' => 'Spoiler'
]);

echo $html;
```
```html
<blockquote><div><cite>John Doe escribió:</cite>Star Wars spoiler <img alt=":)" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/v/latest/svg/1f642.svg"> <details class="spoiler"><summary class="spoiler-header" data-hide="Ocultar" data-show="Mostrar">Spoiler </summary><div class="spoiler-content">Spocks kills Dumbledore</div></details></div></blockquote>
```
