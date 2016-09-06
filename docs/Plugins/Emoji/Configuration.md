### Change the image size

`setImageSize()` will change the image size and automatically use the assets that match it.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->setImageSize(18);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" width="18" height="18" src="//twemoji.maxcdn.com/2/72x72/1f600.png">
```

### Remove the hardcoded size

`omitImageSize()` will remove the dimensions hardcoded in the markup. Emoji images can then be dimensioned in CSS using the `img.emoji` selector.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Use the 72x72 assets but don't hardcode the dimensions in the img element
$configurator->Emoji->setImageSize(72);
$configurator->Emoji->omitImageSize();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" src="//twemoji.maxcdn.com/2/72x72/1f600.png">
```

### Use SVG images

`usePNG()` and `useSVG()` can be used to choose which type of assets to use.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->useSVG();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world 😀';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/svg/1f600.svg">
```

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
Hello world <img alt=":D" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/72x72/1f600.png">
```
