### How attribute filters work

Attribute filters are callbacks that can be used to validate or sanitize user input. During parsing, they are called for each attribute using the attribute's value as sole argument and their return value replaces the attribute's value. If the return value is `false` then the attribute is declared invalid and no more filter is applied.


### Add a default attribute filter

The library provides a [built-in collection of attribute filters](Built-in_filters.md) which cover most of the common cases. In the following example, we create `TEXTSIZE` BBCode with a `size` attribute, to which we add a `#uint` filter to ensure that its value is a positive integer.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create the TEXTSIZE BBCode/tag
$bbcode = $configurator->BBCodes->add('TEXTSIZE');
$tag    = $configurator->tags->add('TEXTSIZE');
$tag->template = '<span style="font-size:{@size}%"><xsl:apply-templates/></span>';

// Create the size attribute
$attribute = $tag->attributes->add('size');
$attribute->filterChain->append('#uint');

extract($configurator->finalize());

$text = '[TEXTSIZE size=1000]This is probably too big[/TEXTSIZE]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:1000%">This is probably too big</span>
```

Some built-in filters may accept arguments. In the following example, instead of using a `#uint` filter, we use a `#range` filter to limit the size between `50` and `500` using the syntax `#range(50, 500)`.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create the TEXTSIZE BBCode/tag
$bbcode = $configurator->BBCodes->add('TEXTSIZE');
$tag    = $configurator->tags->add('TEXTSIZE');
$tag->template = '<span style="font-size:{@size}%"><xsl:apply-templates/></span>';

// Create the size attribute
$attribute = $tag->attributes->add('size');
$attribute->filterChain->append('#range(5, 500)');

extract($configurator->finalize());

$text = '[TEXTSIZE size=1000]This is still pretty big[/TEXTSIZE]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:500%">This is still pretty big</span>
```


### Add a custom attribute filter

In the following example, we create an `[IMG]` BBCode and we add a filter on the `src` attribute to replace the URL with our own.

```php
$configurator = new s9e\TextFormatter\Configurator;

function replaceUrl($attrValue, $proxyUrl)
{
	return $proxyUrl . urlencode($attrValue);
}

// Add the default [IMG] BBCode
$configurator->BBCodes->addFromRepository('IMG');

// Add our custom filter to this attribute. We also need to declare the second
// argument that our callback expects
$configurator->tags['IMG']->attributes['src']->filterChain
	->append('replaceUrl')
	->addParameterByValue('http://example.org/proxy.php?');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[img]http://example.com/img.png[/img]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="http://example.org/proxy.php?http%3A%2F%2Fexample.com%2Fimg.png" title="" alt="">
```
