## Add emoticons

`add()` takes two arguments: the emoticon, and the HTML representing it. This example uses a relative URL but you may want to use an absolute URL instead.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');
$configurator->Emoticons->add(':(', '<img src="sad.png" alt=":(" title="Sad">');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'Hello world :) :('; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img src="happy.png" alt=":)" title="Happy"> <img src="sad.png" alt=":(" title="Sad">
```
