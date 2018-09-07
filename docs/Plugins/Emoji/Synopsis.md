Emoji are ideograms and smileys standardized by the [Unicode Consortium](http://unicode.org/emoji/). The Emoji plugin renders them as images using [EmojiOne](https://emojione.com/) assets. Please consult the EmojiOne website for license terms.

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
Hello world <img alt="😀" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.0/png/64/1f600.png">
```

### Using Twemoji

Starting with 1.3.0, a new attribute `tseq` has been added for compatibility with Twemoji-style filenames.
```php
$configurator = new s9e\TextFormatter\Configurator;

$tag = $configurator->Emoji->getTag();
$tag->template = '<img src="https://twemoji.maxcdn.com/2/svg/{@tseq}.svg">';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '©️ Twitter';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="https://twemoji.maxcdn.com/2/svg/a9.svg"> Twitter
```


### Inputting emoji as codepoints

In some cases, it may be desirable to input emoji as a sequence of codepoints instead of Unicode characters. Codepoints must be expressed in lowercase hexadecimal and be separated by a single dash. For example: `:1f44b-1f3fb:`. [Zero-width joiners](https://en.wikipedia.org/wiki/Zero-width_joiner) and [variation selectors 16](https://en.wikipedia.org/wiki/Variation_Selectors_(Unicode_block)) can be omitted.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :1f937-1f3fe-200d-2640-fe0f:';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt=":1f937-1f3fe-200d-2640-fe0f:" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.0/png/64/1f937-1f3fe-2640.png">
```
