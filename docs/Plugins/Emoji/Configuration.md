### Manage aliases

Aliases can be managed via the `$aliases` property, which can be accessed as an array. See [the NormalizedCollection API](http://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Configurator/Collections/NormalizedCollection.html) for a list of methods.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji->aliases[':D'] = '😀';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello world :D';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Hello world <img alt=":D" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/1f600.png">
```

### Configuring aliases at parsing time

Starting with 1.3, emoji aliases can be read and modified at parsing time via `$parser->registeredVars['Emoji.aliases']`. Do note that while removing any aliases will prevent them from being used, only aliases that start and end with a `:` and only contain lowercase letters, digits, `_`, `-` and `+` can be added.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoji;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hi :smiling_face:';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo "$html\n";

// Add an alias before parsing the text again
$parser->registeredVars['Emoji.aliases'][':smiling_face:'] = '😀';

$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html;
```
```html
Hi :smiling_face:
Hi <img alt=":smiling_face:" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/1f600.png">
```
