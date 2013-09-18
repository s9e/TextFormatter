## Using the Censor plugin in plain text and HTML

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
