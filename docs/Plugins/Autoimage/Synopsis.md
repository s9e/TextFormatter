This plugin converts plain-text image URLs into actual images. Only URLs starting with `http://` or `https://` and ending with `.gif`, `.jpeg`, `.jpg`, `.png`, `.svg`, `.svgz`, or `.webp` are converted.

## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Autoimage');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://example.org/image.png';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="http://example.org/image.png">
```


### Replace the list of file extensions

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Autoimage->fileExtensions = ['avif', 'bmp', 'jpg'];

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'http://example.org/image.bmp';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="http://example.org/image.bmp">
```
