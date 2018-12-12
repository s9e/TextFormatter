This plugin performs generic, regexp-based replacements.
The values in the named capturing subpatterns in the matching regexp are available as attributes in the XSL replacement.

Note that while its API resembles `preg_replace()`, the implementation does *not* use `preg_replace()` and retains all the properties of an XSL template (such as the automatic escaping of HTML characters) as well as s9e\TextFormatter's features such as the detection of blatantly-unsafe markup.

## Examples

### Using the PCRE syntax

Using the PCRE syntax using positional arguments in the template (e.g. `$1`) is the simplest form but the least forward-compatible.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Preg->replace(
	'/@(\\w+)/',
	'<a href="https://twitter.com/$1">$0</a>',
	// The third argument is optional and can be used to configure the tag's name.
	// If you don't provide one, a pseudo-random name will be generated
	'TWITTERUSER'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "Twitter's official tweets @Twitter";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Twitter's official tweets <a href="https://twitter.com/Twitter">@Twitter</a>
```

### Using named subpatterns and XSL

Using named subpatterns and providing a tag name ensures that changes made to either the regexp or the template can be backward- and forward- compatible. Note that the template can use a combination of XSL and PCRE syntax and in this example `$0` and `<xsl:apply-templates/>` are interchangeable.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Preg->replace(
	'/@(?<username>\\w+)/',
	'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>',
	'TWITTERUSER'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

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
$configurator->Preg->replace('/_(.*?)_/', '<em>$1</em>');
$configurator->Preg->replace('/~(.*?)~/', '<s>$1</s>');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'This is _emphasised ~striked~ text_.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
This is <em>emphasised <s>striked</s> text</em>.
```

### Using match()

In the following example, we use BBCodes plugin to implement a `[B]` BBCode for bold then we configure the Preg plugin to match any pair of asterisks with some text in the middle, such as `*text*`. We assign the match to the `B` tag.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->Preg->match('/\\*(.+?)\\*/', 'B');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[b]BBCode[/b] or *Preg*.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<b>BBCode</b> or <b>Preg</b>.
```

## Security

Unsafe markup is rejected and an exception is thrown. The following example will fail because its template uses unfiltered content in a JavaScript context. We use the `highlightNode()` method of `UnsafeTemplateException` to display exactly which node caused the exception to be thrown. Note that it's the XSL representation of the template that is displayed.
```php
try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->Preg->replace(
		'#<script>(.*)</script>#',
		'<pre>
			<code>$1</code>
		</pre>
		<script>$1</script>'
	);
}
catch (s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException $e)
{
	echo $e->getMessage(), "\n\n", $e->highlightNode();
}
```
<pre><code>
Cannot allow unfiltered data in this context

&lt;pre&gt;
  &lt;code&gt;
    &lt;xsl:apply-templates/&gt;
  &lt;/code&gt;
&lt;/pre&gt;
&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;
</code></pre>

Additionally, if a replacement is used as a URL, this plugin will automatically filter it as a URL, using the default URL filter. For instance:
```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Preg->replace(
	'#<(.*?)>#',
	'<a href="$1">$1</a>'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "Good link: <http://example.org/>\n"
      . "Bad link:  <javascript:alert(1)>";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Good link: <a href="http://example.org/">http://example.org/</a>
Bad link:  &lt;javascript:alert(1)&gt;
```
