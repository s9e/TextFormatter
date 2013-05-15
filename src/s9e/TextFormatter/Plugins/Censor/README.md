## Synopsis

This plugin censors text based on a configurable list of words.
Jokers are accepted: `*` matches any number of letters, `?` matches one letter exactly.
Censored words are replaced with `****` unless a replacement is specified when the censored word is added to the list.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Censor->add('apple*');
$configurator->Censor->add('banana*', 'onion');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Putting apples in an applepie. Chopping some bananas on top.'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Putting **** in an ****. Chopping some onion on top.
```