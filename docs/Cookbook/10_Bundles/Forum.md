## Forum, a bundle for forum software

### Example

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = '[quote="John Doe"]Star Wars spoiler: [spoiler]Snapes kills Dumbledore[/spoiler][/quote]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html;
```
```html
<blockquote><div><cite>John Doe wrote:</cite>Star Wars spoiler: <div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;''!==a.display?(a.display=c.display='',b.display='none'):(a.display=c.display='none',b.display='')"><span>Show</span><span style="display:none">Hide</span></button><span class="spoiler-title">Spoiler </span></div><div class="spoiler-content" style="display:none">Snapes kills Dumbledore</div></div></div></blockquote>
```

### Plugins

 * Autoemail
 * Autolink
 * BBCodes
 * Emoticons
 * MediaEmbed

### BBCodes

BBCodes enabled: b, center, code, color, email, i, list, li, *, media, quote, s, size, spoiler, u, url.
Media BBCodes: Bandcamp, Dailymotion, Facebook, Grooveshark, Indiegogo, Instagram, Kickstarter, Liveleak, Soundcloud, Twitch, Vimeo, Vine, WSHH, YouTube.

### Emoticons

*Note: the images are not included in this bundle. If you're looking for a compatible emoticons set I recommend [Tango Smileys Extended](http://wordpress.org/plugins/tango-smileys-extended/screenshots/).*

<table>
<tr><td>:)</td><td><code>&lt;img src="{$EMOTICONS_PATH}/smile.png" alt=":)"/&gt;</td></tr>
<tr><td>:-)</td><td><code>&lt;img src="{$EMOTICONS_PATH}/smile.png" alt=":-)"/&gt;</td></tr>
<tr><td>;)</td><td><code>&lt;img src="{$EMOTICONS_PATH}/wink.png" alt=";)"/&gt;</td></tr>
<tr><td>;-)</td><td><code>&lt;img src="{$EMOTICONS_PATH}/wink.png" alt=";-)"/&gt;</td></tr>
<tr><td>:D</td><td><code>&lt;img src="{$EMOTICONS_PATH}/grin.png" alt=":D"/&gt;</td></tr>
<tr><td>:-D</td><td><code>&lt;img src="{$EMOTICONS_PATH}/grin.png" alt=":-D"/&gt;</td></tr>
<tr><td>:(</td><td><code>&lt;img src="{$EMOTICONS_PATH}/frown.png" alt=":("/&gt;</td></tr>
<tr><td>:-(</td><td><code>&lt;img src="{$EMOTICONS_PATH}/frown.png" alt=":-("/&gt;</td></tr>
<tr><td>:-*</td><td><code>&lt;img src="{$EMOTICONS_PATH}/kiss.png" alt=":-*"/&gt;</td></tr>
<tr><td>:P</td><td><code>&lt;img src="{$EMOTICONS_PATH}/razz.png" alt=":P"/&gt;</td></tr>
<tr><td>:-P</td><td><code>&lt;img src="{$EMOTICONS_PATH}/razz.png" alt=":-P"/&gt;</td></tr>
<tr><td>:p</td><td><code>&lt;img src="{$EMOTICONS_PATH}/razz.png" alt=":p"/&gt;</td></tr>
<tr><td>:-p</td><td><code>&lt;img src="{$EMOTICONS_PATH}/razz.png" alt=":-p"/&gt;</td></tr>
<tr><td>:?</td><td><code>&lt;img src="{$EMOTICONS_PATH}/confused.png" alt=":?"/&gt;</td></tr>
<tr><td>:-?</td><td><code>&lt;img src="{$EMOTICONS_PATH}/confused.png" alt=":-?"/&gt;</td></tr>
<tr><td>:|</td><td><code>&lt;img src="{$EMOTICONS_PATH}/neutral.png" alt=":|"/&gt;</td></tr>
<tr><td>:-|</td><td><code>&lt;img src="{$EMOTICONS_PATH}/neutral.png" alt=":-|"/&gt;</td></tr>
<tr><td>:o</td><td><code>&lt;img src="{$EMOTICONS_PATH}/shock.png" alt=":o"/&gt;</td></tr>
<tr><td>:lol:</td><td><code>&lt;img src="{$EMOTICONS_PATH}/laugh.png" alt=":lol:"/&gt;</td></tr>
</table>

### Parameters

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = '[quote="John Doe"]Star Wars spoiler :) [spoiler]Snapes kills Dumbledore[/spoiler][/quote]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml, [
	// Path to the emoticons
	'EMOTICONS_PATH' => '/path/to/emoticons',

	// Translatable strings used in [quote] and [spoiler]
	'L_WROTE'   => 'escribió:',
	'L_HIDE'    => 'Ocultar',
	'L_SHOW'    => 'Mostrar',
	'L_SPOILER' => 'Spoiler'
]);

echo $html;
```
```html
<blockquote><div><cite>John Doe escribió:</cite>Star Wars spoiler <img src="/path/to/emoticons/smile.png" alt=":)"> <div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;''!==a.display?(a.display=c.display='',b.display='none'):(a.display=c.display='none',b.display='')"><span>Mostrar</span><span style="display:none">Ocultar</span></button><span class="spoiler-title">Spoiler </span></div><div class="spoiler-content" style="display:none">Snapes kills Dumbledore</div></div></div></blockquote>
```
