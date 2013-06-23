## Add custom BBCodes

You can read about the custom syntax in [BBCodeMonkey.md](https://github.com/s9e/TextFormatter/blob/master/docs/BBCodeMonkey.md).
You can find more examples of custom BBCodes in the bundled [repository.xml](https://github.com/s9e/TextFormatter/blob/master/src/s9e/TextFormatter/Plugins/BBCodes/Configurator/repository.xml).

You may also look into [phpBB's own custom BBCodes](https://www.phpbb.com/customise/db/custom_bbcodes-26/) as most of them are compatible.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->BBCodes->addCustom(
	'[size={RANGE=10,32}]{TEXT}[/size]',
	'<span style="font-size:{RANGE}px">{TEXT}</span>'
);

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

// Note how values outside of range are automatically adjusted
$text = '[size=5]Small text[/size], [size=24]big[/size], [size=999]biggest[/size].'; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:10px">Small text</span>, <span style="font-size:16px">big</span>, <span style="font-size:32px">biggest</span>.
```
