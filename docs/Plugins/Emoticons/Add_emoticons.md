<h2>Add emoticons</h2>

`add()` takes two arguments: the emoticon, and the HTML representing it. This example uses a relative URL but you may want to use an absolute URL instead.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');
$configurator->Emoticons->add(':(', '<img src="sad.png" alt=":(" title="Sad">');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :) :(';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img src="happy.png" alt=":)" title="Happy"> <img src="sad.png" alt=":(" title="Sad">
```

### Alternative syntax

`$configurator->Emoticons` can be accessed as an array where keys are the emoticons and values are their templates.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons[':)'] = '<img src="happy.png" alt=":)" title="Happy">';
$configurator->Emoticons[':('] = '<img src="sad.png" alt=":(" title="Sad">';

foreach ($configurator->Emoticons as $emoticon => $template)
{
	echo "$emoticon is for $template\n";
}
```
```html
:) is for <img src="happy.png" alt=":)" title="Happy"/>
:( is for <img src="sad.png" alt=":(" title="Sad"/>
```
