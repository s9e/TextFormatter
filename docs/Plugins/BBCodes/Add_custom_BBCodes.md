<h2>Add custom BBCodes</h2>

You can find more examples of custom BBCodes in the bundled [repository.xml](https://github.com/s9e/TextFormatter/blob/master/src/Plugins/BBCodes/Configurator/repository.xml).

You may also look into [phpBB's own custom BBCodes](https://www.phpbb.com/customise/db/custom_bbcodes-26/) as most of them are compatible.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->BBCodes->addCustom(
	'[size={RANGE=10,32}]{TEXT}[/size]',
	'<span style="font-size:{RANGE}px">{TEXT}</span>'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

// Note how values outside of range are automatically adjusted
$text = '[size=5]Small text[/size], [size=24]big[/size], [size=999]biggest[/size].';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:10px">Small text</span>, <span style="font-size:24px">big</span>, <span style="font-size:32px">biggest</span>.
```

### Allow custom filters

By default, only a few callbacks are allowed to be used as attribute filters in custom BBCodes, for security reasons. You can enable more callbacks in the BBCodeMonkey object used to create custom BBCodes.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = 'md5';
$configurator->BBCodes->addCustom(
	'[md5 hash={ALNUM;useContent;preFilter=md5}]{TEXT}[/md5]',
	'{TEXT} becomes {ALNUM}'
);

extract($configurator->finalize());

$text = '[md5]This text[/md5]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
This text becomes e774cc9902f6011ff96e0b762630fabf
```