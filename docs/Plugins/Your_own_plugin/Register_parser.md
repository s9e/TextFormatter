<h2>Plug your own parser</h2>

You can register your own parser to be executed at runtime with `$parser->registerParser($name, $callback[, $regexp])` in which:

 1. `$name` is the name you want to give to the parser.
 2. `$callback` is a [callback](https://php.net/manual/en/language.types.callable.php) that receives two arguments:
     1. The original text.
     2. An optional array containing the matches of a `preg_match_all()` call if a regexp was configured
 3. `$regexp` is an optional regular expression. If specified, the custom parser is only called if the original text matches the expression. Matches are passed as the callback's second argument.

### Register a parser that is run every time

In this example, we create a tag called `HASH` that our custom parser will add at the start of the text. We add an attribute to that tag to store the MD5 hash for the original text.

```php
// Create a new configuration and create a "HASH" tag with a "value" attribute
$configurator = new s9e\TextFormatter\Configurator;
$configurator->tags->add('HASH')->attributes->add('value');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

// We register our custom parser at runtime
$parser->registerParser(
	'MyParser',
	function ($text, $matches) use ($parser)
	{
		// Add a self-closing tag at position 0 of length 0
		$parser->addSelfClosingTag('HASH', 0, 0)
		       ->setAttribute('value', md5($text));
	}
);

$text = 'This is a text';
$xml  = $parser->parse($text);

echo $xml;
```
```xml
<r><HASH value="0cd0a3afe6daaa52baf1874d56764e79"/>This is a text</r>
```

### Register a parser that is run if the text matches given regexp

In this example, we create a tag called `HEART` that our custom parser will use to replace the string `<3` with a Unicode heart.

```php
// Create a new configuration and create a "HEART" tag that's rendered as a Unicode character
$configurator = new s9e\TextFormatter\Configurator;
$configurator->tags->add('HEART')->template = '&#9829;';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

// We register our custom parser at runtime
$parser->registerParser(
	'MyParser',
	function ($text, $matches) use ($parser)
	{
		// Here, $matches will contain the result of the following instruction:
		// preg_match_all('(<3)', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)
		foreach ($matches as $match)
		{
			// Let's create a self-closing tag around the match
			$parser->addSelfClosingTag('HEART', $match[0][1], 2);
		}
	},
	// Here we pass a regexp as the third argument to indicate that we only want to
	// run this parser if the text matches (<3)
	'(<3)'
);

$text = 'Less than three: <3 <3';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo "XML:  $xml\n";
echo "HTML: $html";
```
```html
XML:  <r>Less than three: <HEART>&lt;3</HEART> <HEART>&lt;3</HEART></r>
HTML: Less than three: ♥ ♥
```
