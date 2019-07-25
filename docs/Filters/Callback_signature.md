<h2>Callback signature</h2>
<style>.main ul { font-size: 16px }</style>

By default, an attribute filter or a tag filter only receives one argument: the attribute's value or the tag, respectively. Additional parameters can be appended with the methods `addParameterByName()` and `addParameterByValue()` and the whole list of parameters can be cleared with `resetParameters()`. Variables set in `$configurator->registeredVars` are available by name and can be changed at parsing time via `$parser->registeredVars`. Other special parameters listed below are available by name.


### Parameters available for all filters

<table style="font-size:75%">
<tr>
	<td><b>logger</b></td>
	<td>The parser's logger.</td>
</tr>
<tr>
	<td><b>registeredVars</b></td>
	<td>All of the registered variables in an array.</td>
</tr>
</table>


### Parameters available for attribute filters

<table style="font-size:75%">
<tr>
	<td><b>attrName</b></td>
	<td>The attribute's name.</td>
</tr>
<tr>
	<td><b>attrValue</b></td>
	<td>The attribute's value.</td>
</tr>
</table>


### Parameters available for tag filters

<table style="font-size:75%">
<tr>
	<td><b>innerText</b><sup>3</sup></td>
	<td>On a paired tag: the text between the two tags.<br>On a single tag: an empty string.</td>
</tr>
<tr>
	<td><b>openTags</b></td>
	<td>An array containing a list of all tags currently open.</td>
</tr>
<tr>
	<td><b>outerText</b><sup>3</sup></td>
	<td>On a paired tag: full text covered by the tag pair.<br>On a single tag: same as <code>tagText</code>.</td>
</tr>
<tr>
	<td><b>parser</b><sup>1</sup></td>
	<td>The parser itself.</td>
</tr>
<tr>
	<td><b>tag</b></td>
	<td>The current tag.</td>
</tr>
<tr>
	<td><b>tagConfig</b><sup>2</sup></td>
	<td>The current tag's configuration.</td>
</tr>
<tr>
	<td><b>tagText</b><sup>3</sup></td>
	<td>The portion of text consumed by this tag.</td>
</tr>
<tr>
	<td><b>text</b></td>
	<td>The text being parsed.</td>
</tr>
</table>

<sup>1</sup> This parameter is skipped in JavaScript filters.  
<sup>2</sup> This parameter is subject to change and may be removed in a future version.  
<sup>3</sup> Available since 2.0.0.  


## Examples

In this example, we create a BBCode that displays the `8` first characters of a given string. The value is static, therefore `addParameterByValue()` is used.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create a [X] BBCode to test our filter
$configurator->BBCodes->addCustom('[X string={TEXT}]', '{@string}');

// Add our custom filter to this attribute. It's equivalent to calling
// substr($attrValue, 0, 8)
$filter = $configurator->tags['X']->attributes['string']->filterChain->append('substr');
$filter->addParameterByValue(0);
$filter->addParameterByValue(8);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[X="1234567890"]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
12345678
```

Now let's modify this example to make the length variable. We create a variable named `myLength` in the configurator with a default value of `8` and use it as a by-name parameter. This variable can be changed at parsing time.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create a myLength variable in the configurator
$configurator->registeredVars['myLength'] = 8;

// Create a [X] BBCode to test our filter
$configurator->BBCodes->addCustom('[X string={TEXT}]', '{@string}');

// Add our custom filter to this attribute. It's equivalent to calling
// substr($attrValue, 0, $myLength)
$filter = $configurator->tags['X']->attributes['string']->filterChain->append('substr');
$filter->addParameterByValue(0);
$filter->addParameterByName('myLength');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[X="1234567890"]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n";

// Change the value at parsing time and try again
$parser->registeredVars['myLength'] = 4;

$text = '[X="1234567890"]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
12345678
1234
```

### Short syntax

In addition to the verbose API, the callback signature can be specified within parentheses using a syntax similar to a subset of PHP. The supported parameter types are:

 - Named parameters, using the variable notation: `$attrValue`
 - Literals: `123`, `'string'` or `"double\nstring"`
 - Booleans and null: `true`, `false` or `null`
 - Short-syntax arrays that do not contain named parameters: `['foo' => 1, 'bar' => 2]`
 - Regexp objects: `/^foo$/i`

The regexp notation exists for compatibility with JavaScript. They are automatically cast as strings in PHP or as [RegExp](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/RegExp) literals in JavaScript. If you don't use JavaScript, you can simply use strings.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom('[X string={TEXT}]', '{@string}');

$configurator->tags['X']->attributes['string']->filterChain
	->append('str_replace("foo", "bar", $attrValue)');

// This is the the same the following:
//
//$configurator->tags['X']->attributes['string']->filterChain
//	->append('str_replace')
//	->resetParameters()
//	->addParameterByValue('foo')
//	->addParameterByValue('bar')
//	->addParameterByName('attrValue');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[X="foo bar baz"]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
bar bar baz
```
