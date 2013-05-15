## Synopsis

This plugin performs generic, regexp-based replacements.
The values in the named capturing subpatterns in the matching regexp are available as attributes in the XSL replacement.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Generic->add(
	// Matching regexp
	'/@(?<username>\\w+)/',
	// XSL replacement
	'<a><xsl:attribute name="href">https://twitter.com/<xsl:value-of select="@username"/></xsl:attribute>@<xsl:value-of select="@username"/></a>'
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = "Twitter's official tweets @Twitter"; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Twitter's official tweets <a href="https://twitter.com/Twitter">@Twitter</a>
```