This plugin converts plain-text URLs into clickable links.
Only URLs starting with a scheme (e.g. "http://") are converted.
Note that by default, the only allowed schemes are "http" and "https".

## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autolink');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'More info at http://example.org.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
More info at <a href="http://example.org">http://example.org</a>.
```

### How to allow more schemes

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autolink');
$configurator->urlConfig->allowScheme('ftp');
$configurator->urlConfig->allowScheme('irc');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Download from ftp://example.org or come chat at irc://example.org/help.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Download from <a href="ftp://example.org">ftp://example.org</a> or come chat at <a href="irc://example.org/help">irc://example.org/help</a>.
```

### Match www. hostnames

By default, only valid URLs that start with a scheme (e.g. `http://`) are matched. If you want to automatically link any hostname that starts with `www.` even if it's not preceded by a scheme, you can enable it during configuration with the `matchWww` option.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Autolink->matchWww = true;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Link: www.example.org.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Link: <a href="http://www.example.org">www.example.org</a>.
```
