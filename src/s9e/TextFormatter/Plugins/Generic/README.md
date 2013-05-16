## Synopsis

This plugin performs generic, regexp-based replacements.
The values in the named capturing subpatterns in the matching regexp are available as attributes in the XSL replacement.

## Examples

### Using the PCRE syntax

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Generic->add(
	'/@(\\w+)/',
	'<a href="https://twitter.com/$1">$0</a>'
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

### Using named subpatterns and XSL

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Generic->add(
	'/@(?<username>\\w+)/',
	'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
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