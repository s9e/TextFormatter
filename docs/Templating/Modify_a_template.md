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
