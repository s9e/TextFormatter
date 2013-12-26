## Synopsis

This plugin performs simple replacements, best suited for handling emoticons.
Matching is case-sensitive.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img src="happy.png" alt=":)" title="Happy">
```

### More examples

You can find more examples [in the Cookbook](https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook#plugins).
