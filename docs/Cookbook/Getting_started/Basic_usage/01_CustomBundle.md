## À la carte: make your own bundle

Predefined bundles are nice, but they're not configurable. Here's how you can create your own bundle. For convenience, we'll base our custom bundle on the default Forum bundle.

```php
$configurator = s9e\TextFormatter\Configurator\Bundles\Forum::getConfigurator();

// Add your own BBCode
$configurator->BBCodes->addCustom(
	'[huge]{TEXT}[/huge]',
	'<span style="font-size:200px">{TEXT}</span>'
);

// Add your own emoticons
$configurator->Emoticons->set(':lol:', '<img src="/path/to/lol.png" alt="LOL"/>');
$configurator->Emoticons->set(':mad:', '<img src="/path/to/mad.png" alt="Mad"/>');

// Also, we want the output to be XHTML, not HTML
$configurator->stylesheet->outputMethod = 'xml';

// Save the bundle to /tmp/Bundle.php
$configurator->saveBundle('My\Bundle', '/tmp/Bundle.php');

// Test your bundle now (notice how [i] was created by the Forum bundle configurator)
include '/tmp/Bundle.php';
$xml  = My\Bundle::parse('[huge][i]Hello[/i][/huge] :lol:');
$html = My\Bundle::render($xml);

echo $html;
```
```html
<span style="font-size:200px"><i>Hello</i></span> <img src="/path/to/lol.png" alt="LOL"/>
```
