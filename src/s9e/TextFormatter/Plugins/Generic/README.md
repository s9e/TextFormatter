## Synopsis

This plugin performs generic, regexp-based replacements.
The values in the named capturing subpatterns in the matching regexp are available as attributes in the XSL replacement.

Note that while the first syntax resembles `preg_replace()`, the implementation does *not* use `preg_replace()` and retains all the properties of an XSL template (such as the automatic escaping of HTML characters) as well as s9e\TextFormatter's features such as the detection of blatantly-unsafe markup.

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

### Unsafe markup

The following example will fail because its template is unsafe.

```php
try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->Generic->add(
		'/<(.*)>/',
		'<a href="$1">$1</a>'
	);
	$configurator->getRenderer();
}
catch (Exception $e)
{
	echo $e->getMessage();
}
```
```html
Attribute '_1' is not properly sanitized to be used in this context
```