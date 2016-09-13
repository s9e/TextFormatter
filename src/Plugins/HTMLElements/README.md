## Synopsis

This plugin enables a whitelist of HTML elements to be used. By default, no HTML elements and no attributes are allowed. For each HTML element, a whitelist of attributes can be set. Unsafe elements such as `<script>` and unsafe attributes such as `onclick` must be set using a different method that safe elements and attributes.

## Examples

### Allowing some safe HTML

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->HTMLElements->allowElement('b');
$configurator->HTMLElements->allowAttribute('b', 'class');
$configurator->HTMLElements->allowElement('i');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '<b>Bold</b> and <i>italic</i> are allowed, but only <b class="important">bold</b> can use the "class" attribute, not <i class="important">italic</i>.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<b>Bold</b> and <i>italic</i> are allowed, but only <b class="important">bold</b> can use the "class" attribute, not <i>italic</i>.
```

### Allowing unsafe HTML

The following will not work.
```php
try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->HTMLElements->allowElement('script');
}
catch (Exception $e)
{
	echo $e->getMessage(), "\n";
}

try
{
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->HTMLElements->allowElement('img');
	$configurator->HTMLElements->allowAttribute('img', 'onerror');
}
catch (Exception $e)
{
	echo $e->getMessage();
}
```
```html
'script' elements are unsafe and are disabled by default. Please use s9e\TextFormatter\Plugins\HTMLElements\Configurator::allowUnsafeElement() to bypass this security measure
'onerror' attributes are unsafe and are disabled by default. Please use s9e\TextFormatter\Plugins\HTMLElements\Configurator::allowUnsafeAttribute() to bypass this security measure
```
Unsafe HTML can still be allowed using `allowUnsafeElement()` and `allowUnsafeAttribute()`.
```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->HTMLElements->allowUnsafeElement('script');
$configurator->HTMLElements->allowElement('b');
$configurator->HTMLElements->allowUnsafeAttribute('b', 'onmouseover');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '<script>alert(1)</script><b onmouseover="alert(1)">Hover me</b>.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<script>alert(1)</script><b onmouseover="alert(1)">Hover me</b>.
```

### More examples

You can find more examples [in the Cookbook](https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook#plugins).
