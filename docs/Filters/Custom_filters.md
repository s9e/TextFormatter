### How attribute filters work

Attribute filters are callbacks. During parsing, they are called with an attribute's value. Their return value becomes the attribute's new value. If they return `false` then the attribute is considered invalid. Attribute filters are used to validate, sanitize and/or transform attribute values.


### Replace a default attribute filter (deprecated)

**⚠️ This usage is deprecated as of s9e\TextFormatter 2.17.0 and will be removed in a future version.**

The default `#int` filter only allows digits to be used. In this example, we replace it with PHP's own `intval()` function which accepts a greater range of values. While the default `#int` filter would reject `4potato` as a valid value, our custom filter will happily convert it to `4`.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Replace #int with intval and indicate that the filter makes values safe in CSS
$filter = $configurator->attributeFilters->set('#int', 'intval');
$filter->markAsSafeInCSS();

// Create a custom BBCode to test our filter
$configurator->BBCodes->addCustom(
	'[size={INT}]{TEXT}[/size]',
	'<span style="font-size:{INT}px">{TEXT}</span>'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[size=4potato]...[/size]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:4px">...</span>
```

### Add a custom attribute filter

**⚠️ This usage is deprecated as of s9e\TextFormatter 2.17.0 and will be removed in a future version.**

The same way default filters can be replaced, new filters can be added. Here, we implement a filter which we call `#funnytext` that will change the capitalization of a string.

```php
$configurator = new s9e\TextFormatter\Configurator;

function mixcase($value)
{
	$str = '';
	foreach (str_split($value, 1) as $i => $char)
	{
		$str .= ($i % 2) ? strtolower($char) : strtoupper($char);
	}
	return $str;
}

// Add our #funnytext filter
$configurator->attributeFilters->set('#funnytext', 'mixcase');

// Create a custom BBCode to test our filter
$configurator->BBCodes->addCustom(
	'[funny]{FUNNYTEXT}[/funny]',
	'{FUNNYTEXT}'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "[funny]To be frank, it's not actually funny.[/funny]";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
To bE FrAnK, iT'S NoT AcTuAlLy fUnNy.
```

### JavaScript filters

Custom PHP filters need custom JavaScript filters in order to work in both environment. For each custom PHP filter you can set a JavaScript function with `setJS()`. The function should take the same arguments and return the same value. For instance, let's add a custom JavaScript filter to the previous example:

```php
// PHP filter
function mixcase($value)
{
	$str = '';
	foreach (str_split($value, 1) as $i => $char)
	{
		$str .= ($i % 2) ? strtolower($char) : strtoupper($char);
	}
	return $str;
}

// JavaScript filter
$js = "
function (value)
{
	var str = '';
	value.split('').forEach(function(char, i)
	{
		str += (i % 2) ? char.toLowerCase() : char.toUpperCase();
	});
	return str;
}
";

// Add our #funnytext filter with its JavaScript counterpart
$configurator->attributeFilters->set('#funnytext', 'mixcase')->setJS($js);
```
