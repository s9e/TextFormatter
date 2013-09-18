## Quickly patch old parsed texts for a new list of words

For performance reasons, s9e\TextFormatter separates parsing from rendering. Since rich content is parsed in advance and can be stored in parsed form, the rendering phase takes very little resources while still allowing for live template updates. At the same time, configuration changes that affect what gets parsed are not retroactive. In most cases, this can be seen as a benefit because it means that things that were not enabled at the time of parsing (e.g. unknown BBCodes, HTML elements) do not suddenly become active later on.

For the Censor plugin on the other hand, it means that words that were not censored at the time of posting remain visible even if you'd want them censored later on. This can be remedied by unparsing the parsed text and re-parsing it. This is already pretty fast, but if updating censored words is all you want to do, the Censor plugin provides an helper class that lets you update a parsed text to censor new words. It comes with some limitations and may apply in contexts where the Censor plugin does not normally apply (for instance, inside of a code block) but in most cases it will produce the same result as unparsing and reparsing a text, and it's really fast.

```php
// Create a configurator and load the Censor plugin
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Censor;

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

// Create an "old" parsed text that contains no censored words.
// The result will be: <pt>Hello world</pt>
$old = $parser->parse('Hello world');

// Configure the Censor plugin to censor a new word, "world"
$configurator->Censor->add('world');

// Create an instance of the helper class. This should be cached for performance
$helper = $configurator->Censor->getHelper();

// Update the parsed text
// The result will be: <rt>Hello <CENSOR>world</CENSOR></rt>
$new = $helper->reparse($old);

// Normally, you would update your database with the new version
if ($new !== $old)
{
	//$db->query('UPDATE ...');
}

// Let's compare the two
echo $renderer->render($old), "\n";
echo $renderer->render($new);
```
```html
Hello world
Hello ****
```
