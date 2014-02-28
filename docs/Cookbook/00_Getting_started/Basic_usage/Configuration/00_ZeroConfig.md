## Zero-configuration: using predefined bundles

Once [installed](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/00_Installation.md), he fastest way to start using s9e\TextFormatter is to use a predefined bundle. In this example, we use the "Forum" bundle, which supports the same kind of formatting that is used in internet forums.

Here is the simplest way to use the Forum bundle:

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text =
	'To-do list:
	[list]
		[*] Say hello to the world :)
		[*] Go to http://example.com
		[*] Try to trip the parser with [b]mis[i]nes[/b]ted[u] tags[/i][/u]
		[*] Watch this video: [media]http://www.youtube.com/watch?v=QH2-TGUlwu4[/media]
	[/list]';

// Parse the original text
$xml = TextFormatter::parse($text);

// Here you should save $xml to your database
// $db->query('INSERT INTO ...');

// Render and output the HTML result
echo TextFormatter::render($xml);

// You can "unparse" the XML to get the original text back
assert(TextFormatter::unparse($xml) === $text);
```
```html
To-do list:
	<ul>
		<li> Say hello to the world <img src="/smile.png" alt=":)"></li>
		<li> Go to <a href="http://example.com">http://example.com</a></li>
		<li> Try to trip the parser with <b>mis<i>nes</i></b><i>ted<u> tags</u></i></li>
		<li> Watch this video: <iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/QH2-TGUlwu4"></iframe></li>
	</ul>
```
