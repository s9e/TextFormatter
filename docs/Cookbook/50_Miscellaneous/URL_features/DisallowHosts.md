## Disallow links pointing to a given domain

In the following example, we disallow links pointing to `example.org` and its subdomains.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->disallowHost('example.org');

// Test the URL config with the Autolink plugin
$configurator->Autolink;

$text = implode("\n", ['http://example.org', 'http://notexample.org', 'http://www.example.org']);
$xml  = $configurator->getParser()->parse($text);
$html = $configurator->getRenderer()->render($xml);

echo $html;
```
```html
http://example.org<br>
<a href="http://notexample.org">http://notexample.org</a><br>
http://www.example.org
```

The asterisk `*` can be used as a joker. In the following example, we disallow links that contain "example" in their host name.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->disallowHost('*example*');

// Test the URL config with the Autolink plugin
$configurator->Autolink;

$text = implode("\n", ['http://example.org', 'http://notexample.org', 'http://www.example.org']);
$xml  = $configurator->getParser()->parse($text);
$html = $configurator->getRenderer()->render($xml);

echo $html;
```
```html
http://example.org<br>
http://notexample.org<br>
http://www.example.org
```

Note that disallowing hosts does not *remove* the host name from the text, it only prevents *linking* to it. The [Censor](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Censor) plugin can be used to censor them in plain text as well.