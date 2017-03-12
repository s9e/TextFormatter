<h2>Runtime configuration</h2>

While most of the configuration happens before the parser and renderer are generated, a few settings can be configured at parsing time and rendering time.

## Parser

### Limit the number/nesting of tags

A tag's `nestingLimit` or `tagLimit` can be set during configuration or before parsing.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->tags->add('X');

// Set the default limits
$configurator->tags['X']->nestingLimit = 1;
$configurator->tags['X']->tagLimit     = 10;

// Get an instance of the parser/renderer
extract($configurator->finalize());

// Change the limits at runtime
$parser->setNestingLimit('X', 10);
$parser->setTagLimit('X', 100);
```

### Toggle a plugin

Plugins can be toggled before parsing.

```php
$parser   = s9e\TextFormatter\Bundles\Forum::getParser();
$renderer = s9e\TextFormatter\Bundles\Forum::getRenderer();

// Disable BBCodes before parsing
$parser->disablePlugin('BBCodes');
$text = '[b]BBCodes disabled[/b]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html, "\n";

// Re-enable BBCodes before parsing
$parser->enablePlugin('BBCodes');
$text = '[b]BBCodes enabled[/b]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html;
```
```html
[b]BBCodes disabled[/b]
<b>BBCodes enabled</b>
```

### Toggle a tag

Individual tags can be toggled before parsing.

```php
$parser   = s9e\TextFormatter\Bundles\Forum::getParser();
$renderer = s9e\TextFormatter\Bundles\Forum::getRenderer();

// Disable B before parsing
$parser->disableTag('B');
$text = '[b]Bold disabled[/b]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html, "\n";

// Re-enable B before parsing
$parser->enableTag('B');
$text = '[b]Bold enabled[/b]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html;
```
```html
[b]Bold disabled[/b]
<b>Bold enabled</b>
```

### Plug your own parser

[You can register your own parser to be executed at runtime.](/Plugins/Your_own_plugin/Register_parser.md)

## Renderer

### Change a template parameter

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom('[hello]', 'Hello <xsl:value-of select="$USER"/>');
$configurator->rendering->parameters['USER'] = 'you';
extract($configurator->finalize());

$text = '[hello]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);
echo $html, "\n";

// Change the USER parameter
$renderer->setParameter('USER', 'Joe');
$html = $renderer->render($xml);
echo $html;
```
```html
Hello you
Hello Joe
```
