### Add aliases to emoji

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->addAlias(':D', '😀');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :D';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt=":D" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/1f600.png">
```
