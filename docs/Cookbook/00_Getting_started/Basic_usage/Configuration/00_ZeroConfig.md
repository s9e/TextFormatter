## Zero-configuration: using predefined bundles

Once [installed](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/00_Installation.md), he fastest way to start using s9e\TextFormatter is to use a predefined bundle. In this example, we use the [Forum bundle](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Forum.md), which supports the same kind of BBCodes/formatting that is used in internet forums. If you prefer Markdown, try the [Fatdown](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Fatdown.md) bundle.

Here is the simplest way to use the Forum bundle:

```php
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = 'To-do list:
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
  <li> Say hello to the world <img alt=":)" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/1f642.png"></li>
  <li> Go to <a href="http://example.com">http://example.com</a></li>
  <li> Try to trip the parser with <b>mis<i>nes</i></b><i>ted<u> tags</u></i></li>
  <li> Watch this video: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4?controls=2"></iframe></span></span></li>
</ul>
```

Or if you prefer Markdown:

```php
use s9e\TextFormatter\Bundles\Fatdown as TextFormatter;

$text = 'To-do list:

  * Say hello to the world :)
  * Go to http://example.com
  * Try to trip the parser with **mis*nes**ted<u> tags*</u>
  * Watch this video: http://www.youtube.com/watch?v=QH2-TGUlwu4';

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
<p>To-do list:</p>

  <ul><li>Say hello to the world :)</li>
  <li>Go to <a href="http://example.com">http://example.com</a></li>
  <li>Try to trip the parser with <strong>mis<em>nes</em></strong><em>ted<u> tags</u></em></li>
  <li>Watch this video: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4?controls=2"></iframe></span></span></li></ul>
```
