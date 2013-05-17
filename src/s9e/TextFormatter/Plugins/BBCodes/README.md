## Synopsis

This plugin handles a very flexible flavour of the [BBCode](http://en.wikipedia.org/wiki/BBCode) syntax.

 * BBCode names are case-insensitive (`[b]...[/B]`)
 * BBCodes can have any number of attributes, which are case-insensitive
 * Default parameters are supported (`[url=...]...[/url]`)
 * Attribute values can optionally be enclosed in single- or double-quotes (`[quote="John Doe"]`, `[quote='John Doe']` or `[quote=John Doe]`)
 * Backslashes can be used to escape quotes in attribute values
 * BBCodes can be self-closed (`[hr/]`)
 * BBCode names can be followed by a colon and a number to uniquely identify and pair them (`[i:123][/i][/i:123]`)

By default, no BBCodes are set. There are several ways to define BBCodes and their template, the easiest two are by adding the predefined BBCodes that exist in the bundled [repository](https://github.com/s9e/TextFormatter/blob/master/src/s9e/TextFormatter/Plugins/BBCodes/Configurator/repository.xml) or by defining them via [their custom syntax](https://github.com/s9e/TextFormatter/blob/master/docs/BBCodeMonkey.md).

## Examples

### Using the bundled repository

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('URL');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Here be [url=http://example.org]the [b]bold[/b] URL[/url].'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Here be <a href="http://example.org">the <b>bold</b> URL</a>.
```

### Using the custom syntax

Note: this syntax is meant to be compatible with [phpBB's custom BBCodes](https://www.phpbb.com/customise/db/custom_bbcodes-26/).

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[COLOR={COLOR}]{TEXT}[/COLOR]',
	'<span style="color:{COLOR}">{TEXT}</span>'
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[color=pink]La vie en rose.[/color]'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="color:pink">La vie en rose.</span>
```