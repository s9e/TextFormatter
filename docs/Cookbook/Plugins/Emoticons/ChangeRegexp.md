## Change the regexp used for emoticons

By default, the Emoticons plugin matches all emoticons regardless of their surroundings. For instance, if two emoticons `:)` and `:(` are defined, the actual regexp (which is generated automatically) will look like this: `/:[()]/S`.

You can change the regexp using the two properties `regexpStart` and `regexpEnd`. By default they are set to `/` and `/S` respectively. In this example, we replace them with `/(?<!\S)` and `(?!\S)/S`. Now the actual regexp will become `/(?<!\S):[()](?!\S)/S` and it will not transform emoticons that are not surrounded with whitespace.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');
$configurator->Emoticons->add(':(', '<img src="sad.png" alt=":(" title="Sad">');

$configurator->Emoticons->regexpStart = '/(?<!\\S)';
$configurator->Emoticons->regexpEnd   = '(?!\\S)/S';

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = "Emoticons surrounded with whitespace: :) :(\nSame without whitespace:              :):(";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Emoticons surrounded with whitespace: <img src="happy.png" alt=":)" title="Happy"> <img src="sad.png" alt=":(" title="Sad"><br>
Same without whitespace:              :):(
```
