## Synopsis

This plugin defines the backslash character `\\` as an escape character.

## Examples

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('Escaper');
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = 'The emoticon \\:) becomes :)'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
The emoticon :) becomes <img src="happy.png" alt=":)" title="Happy">
```

### How to escape only certain characters

By default, this plugin escapes any character following a backslash, using the regular expression `/\\./us`. Expert users can change this regular expression at loading time. Note that only regular expressions starting with a backslash are supported.

In the following example, this plugin is limited to escaping ASCII non-word characters. Other backslashes are preserved.
```php
$configurator = new s9e\TextFormatter\Configurator;

// Here we load the plugin using our custom regexp
$configurator->plugins->load('Escaper', ['regexp' => '/\\\\(?=[[:ascii:]])\\W/s']);

// We disable line breaks for cosmetic purposes, this is unrelated to escaping
$configurator->rootRules->noBrDescendant();

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = <<<END
Backslash before backslash: \\\\
Backslash before bracket:   \\[
Backslash before letter:    s9e\\TextFormatter
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
