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

### Passthrough capture

Multiple replacements can be applied to the same span of text, provided that they use a match-all pattern such as `(.*?)` to display its content.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Generic->add('/_(.*?)_/', '<em>$1</em>');
$configurator->Generic->add('/~(.*?)~/', '<s>$1</s>');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'This is _emphasised ~striked~ text_.'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
This is <em>emphasised <s>striked</s> text</em>.
```

## Unsafe markup

Unsafe markup is rejected and an exception is thrown. The following example will fail because its template uses unfiltered content in a JavaScript context. We use the `highlightNode()` method of `UnsafeTemplateException` to display exactly which node caused the exception to be thrown. Note that it's the XSL representation of the template that is displayed.
```php
try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->Generic->add(
		'#<script>(.*)</script>#',
		'<pre><code>$1</code></pre><script>$1</script>'
	);
}
catch (s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException $e)
{
	echo $e->getMessage(), "\n<code>", $e->highlightNode(), "</code>";
}
```
<pre>
Cannot allow unfiltered data in this context
<code>&lt;pre&gt;
  &lt;code&gt;
    &lt;xsl:apply-templates/&gt;
  &lt;/code&gt;
&lt;/pre&gt;
&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;</code>
</pre>