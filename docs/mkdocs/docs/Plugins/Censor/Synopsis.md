This plugin censors text based on a configurable list of words.

 * The list is not case-sensitive. If you censor "foo", then "FOO" and "Foo" are also censored.
 * Jokers are accepted: `*` matches any number of letters or digits, `?` matches one character exactly.
 * A single space matches any number of whitespace characters, meaning that censoring "b u g" will also censor "bug" or "b  u  g".
 * Censored words are replaced with `****` unless a replacement is specified when the censored word is added to the list.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Censor->add('apple*');
$configurator->Censor->add('banana*', 'onion');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Putting apples in an applepie. Chopping some bananas on top.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Putting **** in an ****. Chopping some onion on top.
```
