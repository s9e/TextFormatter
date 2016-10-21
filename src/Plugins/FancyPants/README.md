## Synopsis

This plugin provides enhanced typography, aka "fancy Unicode symbols." It is inspired by [SmartyPants](http://daringfireball.net/projects/smartypants/) and [RedCloth's Textile](http://redcloth.org/textile/writing-paragraph-text/#typographers-quotes).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('FancyPants');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Fancy "quotes", symbols (c)(tm), dashes -- and elipsis...';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Fancy “quotes”, symbols ©™, dashes – and elipsis…
```