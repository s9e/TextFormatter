<h2>Disallow links pointing to a given domain</h2>

In the following example, we disallow links pointing to `example.org` and its subdomains.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->disallowHost('example.org');

// Test the URL config with the Autolink plugin
$configurator->Autolink;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = implode("\n", ['http://example.org', 'http://notexample.org', 'http://www.example.org']);
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
http://example.org
<a href="http://notexample.org">http://notexample.org</a>
http://www.example.org
```

The asterisk `*` can be used as a joker. In the following example, we disallow links that contain "example" in their host name.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->disallowHost('*example*');

// Test the URL config with the Autolink plugin
$configurator->Autolink;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = implode("\n", ['http://example.org', 'http://notexample.org', 'http://www.example.org']);
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
http://example.org
http://notexample.org
http://www.example.org
```

Note that disallowing hosts does not *remove* the host name from the text, it only prevents *linking* to it. The [Censor](/Plugins/Censor/Synopsis) plugin can be used to censor them in plain text as well.
