## Expert mode: configure everything yourself

s9e\TextFormatter operates in 3 distinct phases: configuration, parsing, rendering.
They are represented by those 3 objects: `s9e\TextFormatter\Configurator`, `s9e\TextFormatter\Parser`, `s9e\TextFormatter\Renderer`.

By separating those operations, we ensure that only the minimum required amount of PHP code is loaded and executed, to remain as efficient and performant as possible without sacrificing features.

Note that this examples assume that you have installed s9e\TextFormatter and loaded the autoloader.

### Configuration

Configuration is the least frequent of the operations. It happens whenever you change the plugins or markup available to the end user, or whenever you change their templates. You will create a new `Configurator`, configure whichever plugins you want to use, whichever BBCodes, emoticons, media sites you want to use. When you're done, generate a new `Parser` and a new `Renderer` which you should cache for performance. You can serialize them with PHP's default [serialize()](http://php.net/manual/en/function.serialize.php) or with PECL's [igbinary](http://pecl.php.net/package/igbinary). You can save them serialized to the disk, you can save them in APC, memcache, your database, whichever is the fastest/most convenient for you.

In the following example, we create a basic configuration using BBCodes, emoticons, auto-linking of URLs and emails as well as embedded media. The instances of `Parser` and `Renderer` are serialized and saved to `/tmp`.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add a bunch of common BBCodes
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('U');
$configurator->BBCodes->addFromRepository('S');
$configurator->BBCodes->addFromRepository('COLOR');
$configurator->BBCodes->addFromRepository('URL');
$configurator->BBCodes->addFromRepository('EMAIL');
$configurator->BBCodes->addFromRepository('CODE');
$configurator->BBCodes->addFromRepository('QUOTE');
$configurator->BBCodes->addFromRepository('LIST');
$configurator->BBCodes->addFromRepository('*');
$configurator->BBCodes->addFromRepository('SPOILER');

// Add a [huge] BBCode that makes text become 200px tall for some reason
$configurator->BBCodes->addCustom(
	'[huge]{TEXT}[/huge]',
	'<span style="font-size:200px">{TEXT}</span>'
);

// Automatically link plain URLs and emails
$configurator->Autolink;
$configurator->Autoemail;

// Load some media sites, this will create a general-purpose [media] BBCode,
// as well as [youtube], [dailymotion], etc...
$configurator->MediaEmbed->createIndividualBBCodes = true;
$configurator->MediaEmbed->add('dailymotion');
$configurator->MediaEmbed->add('liveleak');
$configurator->MediaEmbed->add('youtube');

// Create some emoticons
$configurator->Emoticons->add(':)', '<img src="/path/to/happy.png" alt=":)" title="Happy" />');
$configurator->Emoticons->add(':(', '<img src="/path/to/sad.png" alt=":(" title="Sad" />');

// finalize() will return an array that contains an instance of the parser and
// an instance of the renderer. If you use extract() it will create $parser and
// $renderer. It will also automatically generate tag rules and finalize the
// plugins' config
extract($configurator->finalize());

// We save the parser and the renderer to the disk for easy reuse
file_put_contents('/tmp/parser.txt',   serialize($parser));
file_put_contents('/tmp/renderer.txt', serialize($renderer));
```

### Parsing

Parsing is the second most frequent operation. It happens everytime the end user posts new content, or edits old content. Parsing turns unformatted text into formatted text, an intermediate representation in XML that you are meant to store (in database) in place of the original text. It literally takes [one line of PHP](https://github.com/s9e/TextFormatter/blob/master/src/Unparser.php#L22) to return formatted text to its original form, and barely a few more to render it to HTML.

In the following example, we load the serialized parser from `/tmp/parser.txt` then we parse a text containing a bit of markup. Here, the formatted text is saved to `/tmp/formatted.xml` but you would typically save it to your database instead.

```php
$parser = unserialize(file_get_contents('/tmp/parser.txt'));

$text =
	'To-do list:
	[list]
		[*] Say hello to the world :)
		[*] Go to http://example.com
		[*] Try to trip the parser with [b]mis[i]nes[/b]ted[u] tags[/i][/u]
		[*] Watch this video: [media]http://www.youtube.com/watch?v=QH2-TGUlwu4[/media]
	[/list]';

$xml = $parser->parse($text);
file_put_contents('/tmp/formatted.xml', $xml);
```

### Rendering

Rendering is the most frequent operation. It happens everytime content is displayed. It transforms the intermediate representation into HTML.

In the following example, we load the serialized renderer from `/tmp/renderer.txt` and the formatted text from `/tmp/formatted.xml` then we render the text as HTML and output the result.

```php
$renderer = unserialize(file_get_contents('/tmp/renderer.txt'));

$xml  = file_get_contents('/tmp/formatted.xml');
$html = $renderer->render($xml);

echo $html;
```
```html
To-do list:
	<ul>
		<li> Say hello to the world <img src="/path/to/happy.png" alt=":)" title="Happy"></li>
		<li> Go to <a href="http://example.com">http://example.com</a></li>
		<li> Try to trip the parser with <b>mis<i>nes</i></b><i>ted<u> tags</u></i></li>
		<li> Watch this video: <div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.youtube.com/embed/QH2-TGUlwu4"></iframe></div></div></li>
	</ul>
```
