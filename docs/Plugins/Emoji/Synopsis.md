Emoji are a standardized set of pictographs.  They exists as Unicode characters and ASCII shortcodes. The Emoji plugin renders both as images, using [EmojiOne](https://emojione.com/) assets. Please consult their respective website for license terms.

## Examples

### Using the default set

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/1f600.png">
```
