Storage format
==============

s9e\TextFormatter parses the original text and produces an XML representation of the text and its markup. That XML document is what should be stored in the database. The original text can be extracted from the XML using the `Unparser` class, or be transformed into HTML using a renderer.

The XML representation of a text should *not* be manually altered unless you *really* know what you're doing. If you want to manually alter a text, it's better to do it **before parsing** or **after rendering**. Tools found in the `s9e\TextFormatter\Utils` class and namespace are safe to use at any time.


### Plain text

Input that contains no valid markup is considered plain text. The root node of the XML representation will be `t` and the rest of the document will only contain `br` and `p` tags. As of version 2.x of the library, the XML representation is guaranteed to start with `<t>`.

In the following example, we use the Forum bundle to parse a plain text and see what the XML representation looks like. Nothing special here.

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = 'Plain & boring text.';
$xml  = TextFormatter::parse($text);

echo $xml;
```
```xml
<t>Plain &amp; boring text.</t>
```


### Rich text

Anything that isn't plain text is considered rich text. The root node of the XML representation will be `r` and the rest of the document may contain any other tags, including tags that were not defined during configuration. As of version 2.x of the library, the XML representation is guaranteed to start with `<r` but the root node may also contain XML namespace declarations required for the rest of the document.

In the following example, we use the Forum bundle to parse two links: the first one using the BBCode syntax, and the second using the Autolink plugin. You can see that both links are represented the same way. The only difference are the `s` and `e` elements in which the markup elements (in this case, BBCodes) are saved.

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = "[url=http://example.org]Go to example.org[/url]\n"
      . "http://example.org";
$xml  = TextFormatter::parse($text);

echo $xml;
```
```xml
<r><URL url="http://example.org"><s>[url=http://example.org]</s>Go to example.org<e>[/url]</e></URL><br/>
<URL url="http://example.org">http://example.org</URL></r>
```

In the following example, instead of the Forum bundle, we create a new configurator and load the Litedown plugin to see what links look like when the Markdown syntax is used. We can see that links created with the Markdown syntax are represented by the same structure as links created with the BBCodes syntax or the Autolink plugin.

```php
// Create a new configurator and load the Litedown plugin
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Litedown');

// Create a new parser based on this configuration
extract($configurator->finalize());

// Parse $text using our newly-created parser
$text = '[Go to example.org](http://example.org)';
$xml  = $parser->parse($text);

echo $xml;
```
```xml
<r><p><URL url="http://example.org"><s>[</s>Go to example.org<e>](http://example.org)</e></URL></p></r>
```

Because the XML representation of the links is the same, they are rendered the same way. Moreover, it means that a renderer originally configured for the BBCode syntax will correctly render links that were created with the Markdown syntax. In the following example, we create a new configurator and load the Litedown plugin. We create a new parser, parse a link using the Markdown syntax, then render the XML representation with a different renderer.

```php
// Create a new configurator and load the Litedown plugin
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Litedown');

// Create a new parser based on this configuration
extract($configurator->finalize());

// Parse $text using our newly-created parser
$text = '[Go to example.org](http://example.org)';
$xml  = $parser->parse($text);

// Now let's use the Forum bundle to render a text that was parsed using a
// different configuration
echo s9e\TextFormatter\Bundles\Forum::render($xml);
```
```html
<p><a href="http://example.org">Go to example.org</a></p>
```
