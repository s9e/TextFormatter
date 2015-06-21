<h2>How to restrict where emoticons are parsed</h2>

By default, the Emoticons plugin matches all emoticons regardless of their surroundings.

The properties `notAfter` and `notBefore` can be used to control whether emoticons should be matched before or after a certain type of characters, using a PCRE expression. For instance, if you don't want to match an emoticon that immediately follows a non-space character, you would set `notAfter` to `\S`. If you don't want to match an emoticon that immediately precedes a letter from A to Z or from a to z, you would set `notBefore` to `[A-Za-z]`.

In the following example, we configure the Emoticons plugin to only match emoticons that not surrounded by non-space characters. Note that the double negative does not mean that the emoticons *must* be surrounded by space. They can also be preceded by nothing (at the start of the text) or followed by nothing (at the end of the text.)

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');
$configurator->Emoticons->add(':(', '<img src="sad.png" alt=":(" title="Sad">');

$configurator->Emoticons->notAfter  = '\\S';
$configurator->Emoticons->notBefore = '\\S';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "Emoticons surrounded with whitespace: :) :(\nSame without whitespace:              :):(";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Emoticons surrounded with whitespace: <img src="happy.png" alt=":)" title="Happy"> <img src="sad.png" alt=":(" title="Sad">
Same without whitespace:              :):(
```
