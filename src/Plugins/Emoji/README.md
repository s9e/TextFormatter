## Synopsis

Emoji are a standardized set of pictographs.  They exists as Unicode characters and ASCII shortcodes. The Emoji plugin renders both as images, using the free sets from [Twemoji](http://twitter.github.io/twemoji/) or [Emoji One](http://emojione.com/). Please consult their respective website for license terms.

## Examples

### Using the Twemoji set

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->useTwemoji();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/72x72/1f600.png">
```

### Using the EmojiOne set

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->useEmojiOne();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/1f600.png">
```
