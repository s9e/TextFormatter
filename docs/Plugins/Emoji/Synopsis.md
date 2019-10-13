Emoji are ideograms and smileys standardized by the [Unicode Consortium](http://unicode.org/emoji/). The Emoji plugin renders them as images using [Twemoji](https://twemoji.twitter.com/) assets. Please consult the Twemoji website for license terms and attribution requirements.


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
Hello world <img alt="😀" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/2/svg/1f600.svg">
```


### Using JoyPixels

Check out [JoyPixels's website](https://www.joypixels.com/licenses/free) for license and attribution requirements.

```php
$configurator = new s9e\TextFormatter\Configurator;

$tag = $configurator->Emoji->getTag();
$tag->template = '<img src="https://cdn.jsdelivr.net/gh/joypixels/emoji-assets@master/png/64/{@seq}.png">';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '😍 JoyPixels';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="https://cdn.jsdelivr.net/gh/joypixels/emoji-assets@master/png/64/1f60d.png"> JoyPixels
```


### Inputting emoji as codepoints

In some cases, it may be desirable to input emoji as a sequence of codepoints instead of Unicode characters. Codepoints must be expressed in lowercase hexadecimal and be separated by a single dash. For example: `:1f44b-1f3fb:`. For compatibility with Twemoji, the fully-qualified sequence using [zero-width joiners](https://en.wikipedia.org/wiki/Zero-width_joiner) and [variation selectors 16](https://en.wikipedia.org/wiki/Variation_Selectors_(Unicode_block)) should be used.

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
Hello world <img alt=":1f937-1f3fe-200d-2640-fe0f:" class="emoji" draggable="false" src="https://twemoji.maxcdn.com/2/svg/1f937-1f3fe-200d-2640-fe0f.svg">
```
