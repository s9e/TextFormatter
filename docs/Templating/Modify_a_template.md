<h2>How to modify a template</h2>

### As a string

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('URL');

// Add a target="_blank" attribute to the URL tag's template using
// a pattern replacement
$tag = $configurator->tags['URL'];
$tag->template = preg_replace(
	'(<a\\b(?![^>]*target))',
	'<a target="_blank"',
	$tag->template
);

extract($configurator->finalize());

$text = '[url]http://example.org[/url]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a target="_blank" href="http://example.org" rel="noreferrer">http://example.org</a>
```

### As a DOM tree

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('URL');

// Get the default URL template as a DOMDocument
$dom = $configurator->tags['URL']->template->asDOM();

// Set a target="_blank" attribute to any <a> element
foreach ($dom->getElementsByTagName('a') as $a)
{
	$a->setAttribute('target', '_blank');
}

// Save the changes
$dom->saveChanges();

// Test the new template
extract($configurator->finalize());

$text = '[url]http://example.org[/url]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://example.org" target="_blank" rel="noreferrer">http://example.org</a>
```

Starting with version 2.15.0, DOM manipulation is enhanced with the [SweetDOM 3.0](https://github.com/s9e/SweetDOM#api) API. In the following example, we showcase two different ways to achieve the same result: add an attribute to `a` elements if a given parameter is set. In one case we add `target="_blank"` if `$NEWTAB` is set, and in the other we set `rel="ugc"` if `$UGC` is set.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('URL');

// Modify the template
$dom = $configurator->tags['URL']->template->asDOM();
foreach ($dom->getElementsByTagName('a') as $a)
{
	$a->prependXslIf(test: '$NEWTAB')
	  ->appendXslAttribute(name: 'target', textContent: '_blank');

	$a->prependXslIf('$UGC')->appendXslAttribute('rel', 'ugc');
}
$dom->saveChanges();

// Test the new template
extract($configurator->finalize());

$text = '[url]http://example.org[/url]';
$xml  = $parser->parse($text);

$renderer->setParameter('NEWTAB', '');
$renderer->setParameter('UGC',    '');
echo $renderer->render($xml), "\n";

$renderer->setParameter('NEWTAB', '1');
$renderer->setParameter('UGC',    '1');
echo $renderer->render($xml);
```
```html
<a href="http://example.org">http://example.org</a>
<a href="http://example.org" rel="ugc" target="_blank">http://example.org</a>
```
