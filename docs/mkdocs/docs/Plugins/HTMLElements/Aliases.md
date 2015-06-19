<h2>Alias HTML elements to other tags</h2>

Usually, HTML is meant to be presented to the user as-is. However, sometimes you may want to alias HTML elements and HTML attributes to other tags and attributes. Here is a concrete example: let's say you want to limit the number of links to 3 per text but users can post links many different ways via the `[url]` BBCode, as an `<a href>` element or even in plain text, letting the Autolink plugin linkify them. Your solution is to use the same tag for the 3 different plugins.

In the following example, we configure the HTMLElements plugin to alias `<a href>` to use the same tag as the BBCodes and Autolink plugins. Then we see that despite using 3 different syntaxes, only the first 3 URLs are linked.

```php
$configurator = new s9e\TextFormatter\Configurator;

// The [url] BBCode uses a tag named "URL" with an attribute of the same name
$configurator->BBCodes->addFromRepository('URL');
$configurator->Autolink;

// <a> will use the "URL" tag
$configurator->HTMLElements->aliasElement('a', 'URL');

// The "href" attribute will use the "url" attribute
$configurator->HTMLElements->aliasAttribute('a', 'href', 'url');

// Limit the number of URL tags to 3
$configurator->tags['URL']->tagLimit = 3;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://example.org/1
[url=http://example.org/2]Second link[/url]
<a href="http://example.org/3">The third and last</a>
<a href="http://example.org/4">This one will not be linkified</a>';

$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://example.org/1">http://example.org/1</a>
<a href="http://example.org/2">Second link</a>
<a href="http://example.org/3">The third and last</a>
&lt;a href="http://example.org/4"&gt;This one will not be linkified&lt;/a&gt;
```
