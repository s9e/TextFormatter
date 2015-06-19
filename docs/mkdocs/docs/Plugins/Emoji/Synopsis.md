Emoji are a standardized set of pictographs. They exists as Unicode characters and ASCII shortcodes. The Emoji plugin renders both as images, using the free set from [Emoji One](http://emojione.com/). Please consult their website for license terms.

### Art license terms

Emoji set designed and offered free by [Emoji One](http://emojione.com/).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world ☺';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="☺" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">
```