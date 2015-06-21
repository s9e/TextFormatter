<h2>Unparsing</h2>

Unparsing the XML representation of parsed text will return the original plain text. It's easy and requires not configuration:

```php
// Let's create a parser for the example
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');

// Original text
$text = 'Hello [b]world[/b]!';

// Parsed text: <r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>
$xml = $configurator->getParser()->parse($text);

// Here's how to unparse the XML back to plain text
echo s9e\TextFormatter\Unparser::unparse($xml);
```
```html
Hello [b]world[/b]!
```
