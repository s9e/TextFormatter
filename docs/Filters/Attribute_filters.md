### How attribute filters work

Attribute filters are callbacks. During parsing, they are called for each attribute using the attribute's value as sole argument and their return value replaces the attribute's value. If the return value is `false` then the attribute is declared invalid and no more filter is applied.

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
