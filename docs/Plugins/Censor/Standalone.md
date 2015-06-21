<h2>Using the Censor plugin as a standalone word filter</h2>

The Censor plugin's helper class can be used as a standalone word filter. The censor helper doesn't use the standard parser and renderer and it has minimal dependencies. In the following example, we create an instance of the helper class that we cache in a standard file for performance.

Note that the standard autoloader is still required. See [Installation](../../Getting_started/Installation.md).

```php
$cacheFile = '/tmp/censor.txt';

if (file_exists($cacheFile))
{
	// Unserialize the censor helper from the cache
	$censor = unserialize(file_get_contents($cacheFile));
}
else
{
	// Create a configurator and add all the filtered words
	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->Censor->add('apples', 'oranges');

	// Get the censor helper
	$censor = $configurator->Censor->getHelper();

	// Serialize the censor helper and cache it in a file for performance
	file_put_contents($cacheFile, serialize($censor));
}

echo $censor->censorText('Comparing apples to oranges.'), "\n";
echo $censor->censorHtml('Comparing <b>apples</b> to oranges.');
```
```html
Comparing oranges to oranges.
Comparing <b>oranges</b> to oranges.
```
