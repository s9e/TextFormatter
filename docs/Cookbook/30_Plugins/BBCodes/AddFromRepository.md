## Add BBCodes from the bundled repository

You can see all of the bundled BBCodes in [repository.xml](https://github.com/s9e/TextFormatter/blob/master/src/s9e/TextFormatter/Plugins/BBCodes/Configurator/repository.xml).

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('URL');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Here be [url=http://example.org]the [b]bold[/b] [i]italic[/i] URL[/url].';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Here be <a href="http://example.org">the <b>bold</b> <i>italic</i> URL</a>.
```
