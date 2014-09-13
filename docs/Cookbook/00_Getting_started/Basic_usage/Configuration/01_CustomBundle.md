## À la carte: make your own bundle

Predefined bundles are nice, but they're not configurable. Here's how you can create your own bundle. For convenience, we'll base our custom bundle on the default "Forum" bundle. We load the bundle in our configurator, extend/reconfigure it and save it under a new name.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->loadBundle('Forum');

// Add your own BBCode
$configurator->BBCodes->addCustom(
	'[huge]{TEXT}[/huge]',
	'<span style="font-size:200px">{TEXT}</span>'
);

// Add your own emoticons
$configurator->Emoticons->set(':lol:', '<img src="/path/to/lol.png" alt="LOL"/>');
$configurator->Emoticons->set(':mad:', '<img src="/path/to/mad.png" alt="Mad"/>');

// OPTIONAL: we use the PHP renderer instead of the default XSL renderer. The following code will
//           create a new class file in the /tmp directory. Instead of /tmp you should choose a
//           writable directory that persists between requests.
$configurator->rendering->setEngine('PHP', '/tmp');

// Save the bundle to /tmp/Bundle.php
$configurator->saveBundle('My\Bundle', '/tmp/Bundle.php');

// Test your bundle now (notice how [i] was created by the Forum bundle configurator)
include '/tmp/Bundle.php';
$xml  = My\Bundle::parse('[huge][i]Hello[/i][/huge] :lol:');
$html = My\Bundle::render($xml);

echo $html;
```
```html
<span style="font-size:200px"><i>Hello</i></span> <img src="/path/to/lol.png" alt="LOL">
```
