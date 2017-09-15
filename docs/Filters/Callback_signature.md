<h2>Callback signature</h2>

By default, an attribute filter or a tag filter only receives one argument: the attribute's value or the tag, respectively. Additional parameters can be appended with the methods `addParameterByName()` and `addParameterByValue()` and the whole list of parameters can be cleared with `resetParameters()`. Variables set in `$configurator->registeredVars` are available by name and can be changed at parsing time via `$parser->registeredVars`. Other special parameters listed below are available by name.

### Parameters available for attribute filters

 * `attrName` - the attribute's name
 * `attrValue` - the attribute's value
 * `logger` - the parser's logger
 * `registeredVars` - all of the registered variables in an array

### Parameters available for tag filters

 * `logger` - the parser's logger
 * `openTags` - an array containing a list of all tags currently open
 * `parser` - the parser itself
 * `registeredVars` - all of the registered variables in an array
 * `tag` - the current tag
 * `tagConfig` - the current tag's configuration
 * `text` - the text being parsed

<span style="color:red">⚠</span> The `openTags` and `tagConfig` parameters are subject to change.

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
