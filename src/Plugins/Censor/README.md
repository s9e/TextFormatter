## Synopsis

This plugin censors text based on a configurable list of words.

 * The list is not case-sensitive. If you censor "foo", then "FOO" and "Foo" are also censored.
 * Jokers are accepted: `*` matches any number of letters or digits, `?` matches one character exactly.
 * A single space matches any number of whitespace characters, meaning that censoring "b u g" will also censor "bug" or "b  u  g".
 * Censored words are replaced with `****` unless a replacement is specified when the censored word is added to the list.

## Examples

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

### Using the Censor plugin in plain text

The Censor plugin provides an helper class that can be used to replace words in plain text (`censorText()`) or in HTML (`censorHtml()`) without fully parsing and rendering the text.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Censor->add('apples', 'oranges');

// NOTE: can be serialized and cached for performance
$helper = $configurator->Censor->getHelper();

$text = 'Comparing apples to oranges.';

echo $helper->censorText($text);
```
```html
Comparing oranges to oranges.
```

Unlike `censorText()`, the `censorHtml()` method will replace words within text nodes but will *not* touch tag names, attribute names, or attribute values.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Censor->add('strong');
$configurator->Censor->add('id');

$helper = $configurator->Censor->getHelper();

$html = 'Some <strong id="id">strong id</strong>';

echo $helper->censorText($html), "\n";
echo $helper->censorHtml($html);
```
```html
Some <**** ****="****">**** ****</****>
Some <strong id="id">**** ****</strong>
```

### More examples

You can find more examples [in the plugin's documentation](http://s9etextformatter.readthedocs.io/Plugins/Censor/Synopsis/).
