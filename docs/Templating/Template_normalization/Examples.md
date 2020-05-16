<h2>Examples</h2>


### Add rel="ugc" to links

The `AddAttributeValueToElements` normalizer can be used to add a value to a list of space-separated values. For instance, it can add a link type to a link's `rel`, or a class name to an element's `class`.

In the following example, we target all `a` elements with the XPath query `//a` in order to add `ugc` to the list of link types stored in the element's `rel` attribute.

```php
use s9e\TextFormatter\Configurator\TemplateNormalizations\AddAttributeValueToElements;

// Create a new configurator and enable the Autolink plugin
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Autolink;

// Add our custom normalizer
$configurator->templateNormalizer->add(
	new AddAttributeValueToElements('//a', 'rel', 'ugc')
);

extract($configurator->finalize());

$text = 'https://example.org';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="https://example.org" rel="ugc">https://example.org</a>
```


### Set loading="lazy" on images

In the following example, we use the `SetAttributeOnElements` normalizer to add a `loading` attribute to images that do not have one.

```php
use s9e\TextFormatter\Configurator\TemplateNormalizations\SetAttributeOnElements;

// Create a new configurator and enable the Autoimage plugin
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Autoimage;

// Add our custom normalizer
$configurator->templateNormalizer->add(
	new SetAttributeOnElements('//img[not(@loading)]', 'loading', 'lazy')
);

extract($configurator->finalize());

$text = 'https://example.org/img.png';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="https://example.org/img.png" loading="lazy">
```
