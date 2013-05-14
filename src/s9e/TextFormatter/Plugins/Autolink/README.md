## Synopsis

This plugin converts plain-text URLs into clickable links.
Only URLs starting with a scheme (e.g. "http://") are converted.
Note that by default, the only allowed schemes are "http" and "https".

## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autolink');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

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

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Download from ftp://example.org or come chat at irc://example.org/help.'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Download from <a href="ftp://example.org">ftp://example.org</a> or come chat at <a href="irc://example.org/help">irc://example.org/help</a>.
```
