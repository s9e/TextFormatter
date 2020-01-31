This plugin handles a very flexible flavour of the [BBCode](https://en.wikipedia.org/wiki/BBCode) syntax.

 * BBCode names are case-insensitive (`[b]...[/B]`)
 * BBCodes can have any number of attributes, which are case-insensitive
 * Default parameters are supported (`[url=...]...[/url]`)
 * Attribute values can optionally be enclosed in single- or double-quotes (`[quote="John Doe"]`, `[quote='John Doe']` or `[quote=John Doe]`)
 * Backslashes can be used to escape quotes in attribute values
 * BBCodes can be self-closed (`[hr/]`)
 * BBCode names can be followed by a colon and a number to uniquely identify and pair them (`[i:123][/i][/i:123]`)

By default, no BBCodes are set. There are several ways to define BBCodes and their template, the easiest two are by adding the predefined BBCodes that exist in the bundled [repository](https://github.com/s9e/TextFormatter/blob/master/src/Plugins/BBCodes/Configurator/repository.xml) or by defining them via [their custom syntax](Custom_BBCode_syntax.md).

## Examples

### Using the bundled repository

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('URL');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Here be [url=http://example.org]the [b]bold[/b] URL[/url].';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Here be <a href="http://example.org">the <b>bold</b> URL</a>.
```

### Using the custom syntax

Note: this syntax is meant to be compatible with [phpBB's custom BBCodes](https://www.phpbb.com/customise/db/custom_bbcodes-26).

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[COLOR={COLOR}]{TEXT}[/COLOR]',
	'<span style="color:{COLOR}">{TEXT}</span>'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[color=pink]La vie en rose.[/color]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="color:pink">La vie en rose.</span>
```

## Security

Unsafe markup and unsafe BBCode definitions are rejected, and an exception is thrown. The following will fail because unfiltered content is used in a JavaScript context. We use `UnsafeTemplateException::highlightNode()` to display exactly which node caused the exception to be thrown. In this case, `title="{@title}"` is fine, but `onclick="{@title}"` is not. Note that the XSL representation of the template is used.

```php
try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->BBCodes->addCustom(
		'[url={URL} title={TEXT1}]{TEXT2}[/url]',
		'<a href="{URL}" title="{TEXT1}" onclick="{TEXT1}">{TEXT2}</a>'
	);
}
catch (s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException $e)
{
	echo $e->getMessage(), "\n\n", $e->highlightNode();
}
```
<pre><code>
Attribute 'title' is not properly sanitized to be used in this context

&lt;a href=&quot;{@url}&quot; title=&quot;{@title}&quot; <span style="background-color:#ff0">onclick=&quot;{@title}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;
</code></pre>
