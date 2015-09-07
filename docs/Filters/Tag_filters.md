### How tag filters work

Tag filters are callbacks. During parsing, they are called for each tag using the tag as the callback's sole argument and their return value indicates whether this tag is valid. Tag filters can be used to validate tags and modify their attributes.

### Add a custom tag filter

In the following example, we create a BBCode named `block` that takes two attributes: `width` and `height`. Then we add a filter to this tag that verifies that the product of `width` × `height` is between 100 and 1000.

```php
$configurator = new s9e\TextFormatter\Configurator;

function blocktagfilter($tag)
{
	$product = $tag->getAttribute('width') * $tag->getAttribute('height');

	return ($product >= 100 && $product <= 1000);
}

// Create a [block] BBCode to test our filter
$configurator->BBCodes->addCustom(
	'[block width={INT} height={INT}]',
	'<div style="width:{@width}px;height:{@height}px" class="block"></div>'
);

// Add our custom filter to this tag
$configurator->tags['block']->filterChain[] = 'blocktagfilter';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[block width=10 height=10][block width=99 height=99]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<div style="width:10px;height:10px" class="block"></div>[block width=99 height=99]
```
