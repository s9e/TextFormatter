## Synopsis

Emoji are a standardized set of pictographs. They exists as Unicode characters and ASCII shortcodes. The Emoji plugin renders both as images, using the free set from [Emoji One](http://emojione.com/). Please consult their website for license terms.

## Examples

### Parse emoji

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

### Convert emoji to their ASCII short name

Some emoji use Unicode's [Supplementary Multilingual Plane](http://en.wikipedia.org/wiki/Supplementary_Multilingual_Plane) which is not always well supported by databases, making it impossible to save them. You can convert emoji to ASCII using the Emoji plugin's Helper class. The Unicode form and the ASCII form will display the same image.

```php
use s9e\TextFormatter\Plugins\Emoji\Helper;

$text = 'Hello world ☺';
$text = Helper::toShortName($text);
echo $text, "\n";

// Get an instance of the parser and the renderer
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;
extract($configurator->finalize());
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world :relaxed:
Hello world <img alt=":relaxed:" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">
```

### Art license terms

Emoji set designed and offered free by [Emoji One](http://emojione.com/).
