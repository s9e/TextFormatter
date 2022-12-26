<h2>Using predefined bundles</h2>

Once [installed](Installation.md), the fastest way to start using s9e\TextFormatter is to use a predefined bundle. In this example, we use the [Forum bundle](../Bundles/Forum.md), which supports the same kind of BBCodes/formatting that is used in internet forums. If you prefer Markdown, try the [Fatdown](../Bundles/Fatdown.md) bundle. You can also use them as a starting point to [make your own bundle](../Bundles/Your_own_bundle.md).


### Forum bundle (BBCodes)

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
  <li> Say hello to the world <img alt=":)" class="emoji" draggable="false" src="https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f642.svg"></li>
  <li> Go to <a href="http://example.com">http://example.com</a></li>
  <li> Try to trip the parser with <b>mis<i>nes</i></b><i>ted<u> tags</u></i></li>
  <li> Watch this video: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span></li>
</ul>
```


### Fatdown bundle (Markdown)

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
  <li>Watch this video: <span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span></li></ul>
```
