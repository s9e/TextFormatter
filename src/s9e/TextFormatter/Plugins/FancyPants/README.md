## Synopsis

This plugin provides enhanced typography, aka "fancy Unicode symbols." It is loosely based on [SmartyPants](http://daringfireball.net/projects/smartypants/) and [RedCloth's Textile](http://redcloth.org/textile/writing-paragraph-text/#typographers-quotes).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('FancyPants');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Fancy "quotes", symbols (c)(tm), dashes -- and elipsis...'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Fancy “quotes”, symbols ©™, dashes – and elipsis…
```