This plugin defines the backslash character `\` as an escape character.

## Examples

### Using the default escape list

By default, this plugin treats most ASCII punctuation as escapable.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Escaper');
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'The emoticon \\:) becomes :)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
The emoticon :) becomes <img src="happy.png" alt=":)" title="Happy">
```

### Escape any Unicode character

By calling `$plugin->escapeAll()`, any character can be escaped. Attention, this is only suitable in some specific situations.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Escaper->escapeAll();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 's9e\\TextFormatter -- s9e\\\\TextFormatter';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
s9eTextFormatter -- s9e\TextFormatter
```

### How to escape only certain characters

Expert users can change the regular expression that matches escapable characters at loading time. Note that only regular expressions starting with a backslash are supported.

In the following example, this plugin is limited to escaping ASCII non-word characters. Other backslashes are preserved.
```php
$configurator = new s9e\TextFormatter\Configurator;

// Here we load the plugin using our custom regexp
$configurator->plugins->load('Escaper', ['regexp' => '/\\\\(?=[[:ascii:]])\\W/s']);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = <<<'END'
Backslash before backslash: \\
Backslash before bracket:   \[
Backslash before letter:    s9e\TextFormatter
END;

$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Backslash before backslash: \
Backslash before bracket:   [
Backslash before letter:    s9e\TextFormatter
```
