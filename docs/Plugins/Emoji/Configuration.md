### Manage aliases

Aliases can be managed via the `$aliases` property, an associative array that maps aliases (as keys) to fully-qualified emoji or emoji sequences (as values.)

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
Hello world <img alt=":D" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.0/png/64/1f600.png">
```

### Configure aliases at parsing time

Starting with 1.3, emoji aliases can be read and modified at parsing time via `$parser->registeredVars['Emoji.aliases']` in PHP and `s9e.TextFormatter.registeredVars['Emoji.aliases']` in JavaScript. Do note that while removing an alias will prevent it from being used, only aliases that start and end with a `:` and only contain lowercase letters, digits, `_`, `-` and `+` can be added at parsing time.

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
Hi <img alt=":smiling_face:" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.0/png/64/1f600.png">
```
