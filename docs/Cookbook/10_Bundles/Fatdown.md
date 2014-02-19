## Fatdown, a Markdown bundle that doesn't suck too bad

### Example

```php
use s9e\TextFormatter\Bundles\Fatdown as TextFormatter;

$text = '**Fatdown** implements a Markdown-like syntax plus extra "stuff".';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html;
```
```html
<p><strong>Fatdown</strong> implements a Markdown-like syntax plus extra “stuff”.</p>
```

### Plugins

 * Autoemail
 * Autolink
 * Escaper
 * FancyPants
 * HTMLElements
 * HTMLEntities
 * Litedown
 * MediaEmbed

### HTMLElements

 * `a`, with optional `href` and `title` attributes
 * `abbr` with an optional `title` attribute
 * `b`, `em`, `i`, `s`, `strong`, `u`
 * `br`
 * `code`
 * `del` and `ins`
 * `hr`
 * `img`, with an optional `src` attribute
 * `sub` and `sup`
 * `table`, `tbody`, `tfoot`, `thead`
 * `td` with optional `colspan` and `rowspan` attributes
 * `th` with optional `colspan`, `rowspan` and `scope` attributes

### MediaEmbed

URLs from the following sites are automatically embedded: Bandcamp, Dailymotion, Facebook, Grooveshark, Liveleak, Soundcloud, Spotify, Twitch, Vimeo, Vine and YouTube.
