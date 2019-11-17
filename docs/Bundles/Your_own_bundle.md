## Your own bundle

To create your own bundle, first configure all of the plugins and settings, then call `$configurator->saveBundle()` with the name of the class and where to save it as a file.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('URL');
$configurator->Emoticons->add(':)', '/path/to/img.png');

$success = $configurator->saveBundle('MyBundle', '/tmp/MyBundle.php');
var_dump($success);
```
```
bool(true)
```

Then you can use your own bundle as you would any other:
```php
// Include the bundle's file manually if your autoloader isn't configured for it
include_once '/tmp/MyBundle.php';

$text = '[b]Hello World[/b]';
$xml  = MyBundle::parse($text);
$html = MyBundle::render($xml);

echo $html;
```
```html
<b>Hello World</b>
```


### Customize a bundle

For convenience, you can use an existing bundle as a starting point to create your own. To do so, instead of creating a new configurator, you can obtain an instance of the bundle's configurator. Then you can save it back as your own bundle.

```php
// Use the Forum bundle's configurator
$configurator = s9e\TextFormatter\Configurator\Bundles\Forum::getConfigurator();

// Customize it to your need
$configurator->HTMLElements->allowElement('b');
$configurator->HTMLElements->allowElement('i');
$configurator->HTMLElements->allowElement('u');

// Save it back as your own
$configurator->saveBundle('MyBundle', '/tmp/MyBundle.php');
```


### Prepare your bundle for redistribution

If you intend to redistribute your bundle, you may want to use a native PHP renderer rather than the default XSLT renderer. A native PHP renderer compiles the XSL templates to native PHP and does not require the `ext/xsl` extension.

To make maintenance easier, you may create your own bundle configurator by extending `s9e\TextFormatter\Configurator\Bundle`. For convenience, your configurator and the generated files (the bundle file and the PHP renderer) should all live in the same namespace and rely on your normal autoloader.

The following code is what a minimal bundle configurator can look like. You can see a more complete example in [the My\Project repository](https://github.com/s9e/MyProject).

```php
namespace My\Project\TextFormatter;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle as AbstractBundleConfigurator;

class BundleConfigurator extends AbstractBundleConfigurator
{
	public function configure(Configurator $configurator): void
	{
		// Configure plugins
		$configurator->BBCodes->addFromRepository('B');

		// Configure the PHP renderer to exist in the current namespace
		$configurator->rendering->engine            = 'PHP';
		$configurator->rendering->engine->className = __NAMESPACE__ . '\\Renderer';
		$configurator->rendering->engine->filepath  = __DIR__ . '/Renderer.php';
	}

	public static function saveBundle(): bool
	{
		$configurator = (new static)->getConfigurator();

		return $configurator->saveBundle(
			__NAMESPACE__ . '\\Bundle',
			__DIR__ . '/Bundle.php',
			['autoInclude' => false]
		);
	}
}
```
