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
<blockquote><div><cite>John Doe wrote:</cite>Star Wars spoiler: <div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?'':'none';return!1"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler </span></div><div class="spoiler-content" style="display:none">Snapes kills Dumbledore</div></div></div></blockquote>
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

Emoji are supported thanks to the free image set from [EmojiOne](https://emojione.com/). Please consult their website for [license terms](http://emojione.com/licensing/).

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
<blockquote><div><cite>John Doe escribió:</cite>Star Wars spoiler <img alt=":)" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/1f642.png"> <div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?'':'none';return!1"><span>Mostrar</span><span style="display:none">Ocultar</span></button><span class="spoiler-title">Spoiler </span></div><div class="spoiler-content" style="display:none">Spocks kills Dumbledore</div></div></div></blockquote>
```
