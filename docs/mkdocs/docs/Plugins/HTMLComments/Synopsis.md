This plugins allows HTML comments to be used. Internet Explorer's conditional comments are explicitly disabled because they could pose a security risk as they could be used to bypass the built-in template security.

The characters `<` or `>` are removed from the comments' contents, as well as the illegal sequence `--`.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('HTMLComments');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '<!-- comment -->';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<!-- comment -->
```
