<h2>Using default sites</h2>

A collection of site definitions is available by default via the `defaultSites` property. The collection can be accessed as an array or via [this API](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/Collections/SiteDefinitionCollection.html) and can be iterated. Each entry contains the definition for a given site as well as optional information such as the site's name or homepage URL.


### Read the list of default sites

```php
$configurator = new s9e\TextFormatter\Configurator;

foreach ($configurator->MediaEmbed->defaultSites as $siteId => $siteConfig)
{
	echo "$siteId => ", print_r(array_filter($siteConfig), true);

	// We only need one entry for this example
	break;
}
```
```
abcnews => Array
(
    [example] => https://abcnews.go.com/WNN/video/dog-goes-wild-when-owner-leaves-22936610
    [extract] => Array
        (
            [0] => !abcnews\.go\.com/(?:video/embed\?id=|[^/]+/video/[^/]+-)(?'id'\d+)!
        )

    [homepage] => https://abcnews.go.com/
    [host] => Array
        (
            [0] => abcnews.go.com
        )

    [iframe] => Array
        (
            [src] => //abcnews.go.com/video/embed?id={@id}
        )

    [name] => ABC News
    [tags] => Array
        (
            [0] => news
        )

)
```

### Modify the default sites collection

```php
$configurator = new s9e\TextFormatter\Configurator;

echo 'Does YouTube exist? ';
echo $configurator->MediaEmbed->defaultSites->exists('youtube') ? "yes\n" : "no\n";

// Delete YouTube
$configurator->MediaEmbed->defaultSites->delete('youtube');
echo 'What about now? ';
echo $configurator->MediaEmbed->defaultSites->exists('youtube') ? "yes\n" : "no\n";

// Replace it with your own
$configurator->MediaEmbed->defaultSites->add(
	'youtube',
	[
		'host'    => 'youtu.be',
		'extract' => '!youtu\.be/(?<id>\w+)!',
		'iframe'  => ['src' => '//www.youtube.com/embed/{@id}']
	]
);

// Or remove them all
echo count($configurator->MediaEmbed->defaultSites), " sites remaining.\n";
$configurator->MediaEmbed->defaultSites->clear();
echo count($configurator->MediaEmbed->defaultSites), " sites remaining.\n";
```
```
Does YouTube exist? yes
What about now? no
124 sites remaining.
0 sites remaining.
```
