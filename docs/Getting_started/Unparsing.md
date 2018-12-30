<h2>Unparsing</h2>

Unparsing the XML representation of parsed text will return the original plain text. It's easy and requires no configuration.

```php
// Let's create a parser for the example
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');

// Create $parser and $renderer
extract($configurator->finalize());

// Original text
$text = 'Hello [b]world[/b]!';

// Parsed text: <r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>
$xml = $parser->parse($text);

// Here's how to unparse the XML back to plain text
echo s9e\TextFormatter\Unparser::unparse($xml);
```
```
Hello [b]world[/b]!
```

### Removing markup

If the goal is to produce a plain version of the original text *without* the markup, `s9e\TextFormatter\Utils::removeFormatting()` can be used instead. It will remove most markup (BBCodes, Litedown, etc...) but will preserve emoticons and emoji.

```php
// Let's create a parser for the example
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->Emoticons->add(':)', '<img src="smile.png"/>');

// Create $parser and $renderer
extract($configurator->finalize());

// Original text
$text = 'Hello [b]world[/b]! :)';

// Parsed text: <r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>
$xml = $parser->parse($text);

// Remove most of the markup from the XML and return a plain text version of the text
echo s9e\TextFormatter\Utils::removeFormatting($xml);
```
```
Hello world! :)
```