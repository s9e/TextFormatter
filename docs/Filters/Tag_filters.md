### How tag filters work

Tag filters are callbacks executed during parsing. They are called for each tag using the tag as the callback's sole argument. Tag filters can be used to invalidate tags or modify their attributes.


### Add a custom tag filter

In the following example, we create a BBCode named `block` that takes two attributes: `width` and `height`. Then we add a filter to this tag that invalidates the tag if the product of `width` × `height` is not between 100 and 1000.

```php
$configurator = new s9e\TextFormatter\Configurator;

function myfilter($tag)
{
	$product = $tag->getAttribute('width') * $tag->getAttribute('height');

	if ($product < 100 || $product > 1000)
	{
		$tag->invalidate();
	}
}

// Create a [block] BBCode to test our filter
$configurator->BBCodes->addCustom(
	'[block width={INT} height={INT}]',
	'<div style="width:{@width}px;height:{@height}px" class="block"></div>'
);

// Add our custom filter to this tag
$configurator->tags['block']->filterChain[] = 'myfilter';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "[block width=10 height=10]\n"
      . "[block width=99 height=99]";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<div style="width:10px;height:10px" class="block"></div>
[block width=99 height=99]
```


### Filter order

By default, a tag's filter chain starts with two callbacks. They are:

 1. `s9e\TextFormatter\Parser\FilterProcessing::executeAttributePreprocessors`
 2. `s9e\TextFormatter\Parser\FilterProcessing::filterAttributes`

You can manage filters using the [TagFilterChain API](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Configurator/Collections/TagFilterChain.html). Filters are executed in order, and attribute values are validated by the `filterAttributes` callback. If you add your own filter before validation, any attributes created or modified by your filter will be validated as if it was normal user input. If you add your filter *after* the `filterAttributes` callback, their value will *not* be validated, allowing you to set values that a user wouldn't be allowed to.

In the following example, we create a BBCode that accepts two optional attributes, set to be validated as numbers: `x` and `y`. We then add two tag filters: the first one sets `x`'s value to `potato` (which is not a number) at the start of the chain *before* validation, and the second filter does the samefor `y` but at the end of the chain *after* validation. In the end, `x`'s value gets removed as invalid while `y`'s value remains.

```php
function setTagAttribute($tag, $attrName, $attrValue)
{
	$tag->setAttribute($attrName, $attrValue);
}

$configurator = new s9e\TextFormatter\Configurator;

$configurator->BBCodes->addCustom('[test x={NUMBER1?} y={NUMBER2?}]', '');

// Add our custom filters to this tag
$configurator->tags['test']->filterChain->prepend('setTagAttribute($tag, "x", "potato")');
$configurator->tags['test']->filterChain->append ('setTagAttribute($tag, "y", "potato")');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[test x=1 y=2]';
$xml  = $parser->parse($text);

echo $xml;
```
```xml
<r><TEST y="potato">[test x=1 y=2]</TEST></r>
```
