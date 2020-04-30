### Manage aliases

Aliases can be managed via the `$aliases` property, an associative array that maps aliases (as keys) to fully-qualified emoji or emoji sequences (as values.)

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->aliases[':D'] = '😀';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :D';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt=":D" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/v/latest/svg/1f600.svg">
```

### Configure aliases at parsing time

Starting with 1.3, emoji aliases can be read and modified at parsing time via `$parser->registeredVars['Emoji.aliases']` in PHP and `s9e.TextFormatter.registeredVars['Emoji.aliases']` in JavaScript. Do note that while removing an alias will prevent it from being used, only aliases that start and end with a `:` and only contain lowercase letters, digits, `_`, `-` and `+` can be added at parsing time.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hi :smiling_face:';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo "$html\n";

// Add an alias before parsing the text again
$parser->registeredVars['Emoji.aliases'][':smiling_face:'] = '😀';

$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html;
```
```html
Hi :smiling_face:
Hi <img alt=":smiling_face:" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/v/latest/svg/1f600.svg">
```

### Using Twemoji assets with text parsed on an older release

Before 1.3, support for Twemoji was incomplete due to a discrepancy between Twemoji's file naming scheme and the default set used by the plugin. Starting with 1.3, a `tseq` attribute has been added to the output and can be used to display Twemoji assets from their CDN. Reparsing the old text fixes this issue. Alternatively, it is possible to use an `xsl:choose` conditional to use a different image source depending on the presence of the `tseq` attribute.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->getTag()->template =
	'<xsl:choose>
		<xsl:when test="@tseq">
			<img alt="{.}" class="emoji" draggable="false"
				src="//twemoji.maxcdn.com/v/latest/svg/{@tseq}.svg"/>
		</xsl:when>
		<xsl:otherwise>
			<img alt="{.}" class="emoji" draggable="false"
				src="https://cdn.jsdelivr.net/gh/s9e/emoji-assets-twemoji@12.0.1/dist/svgz/{@seq}.svgz"/>
		</xsl:otherwise>
	</xsl:choose>';

extract($configurator->finalize());

$xml = "<r><EMOJI seq=\"00a9\">\u{a9}\u{fe0f}</EMOJI>\n"
     . "<EMOJI tseq=\"a9\" seq=\"00a9\">\u{a9}\u{fe0f}</EMOJI></r>";

echo $renderer->render($xml);
```
```html
<img alt="©️" class="emoji" draggable="false" src="https://cdn.jsdelivr.net/gh/s9e/emoji-assets-twemoji@12.0.1/dist/svgz/00a9.svgz">
<img alt="©️" class="emoji" draggable="false" src="//twemoji.maxcdn.com/v/latest/svg/a9.svg">
```
