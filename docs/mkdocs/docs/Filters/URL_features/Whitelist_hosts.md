<h2>Restrict links and/or images to a set of whitelisted domains</h2>

In the following example, we disallow all URLs that do not point to `example.org` or `example.com` and their subdomains.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->restrictHost('example.com');
$configurator->urlConfig->restrictHost('example.org');

// Test the URL config with the Autolink plugin
$configurator->Autolink;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = implode("\n", ['http://example.com', 'http://notexample.org', 'http://www.example.org']);
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://example.com">http://example.com</a>
http://notexample.org
<a href="http://www.example.org">http://www.example.org</a>
```

### Restrict images to a set of allowed hosts

In the following example we create a special instance of UrlConfig with a different set of rules, then we use exclusively for the filters applied to the `IMG` tag. For convenience, we use the BBCodes plugin.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create a special instance of UrlConfig that restrict URLs to i.imgur.com and tinypic.com
$urlConfig = new s9e\TextFormatter\Configurator\UrlConfig;
$urlConfig->restrictHost('i.imgur.com');
$urlConfig->restrictHost('tinypic.com');

// Create an [img] BBCode for images and [link] BBCode for links
$configurator->BBCodes->addCustom(
	'[img src={URL} /]',
	'<img src="{URL}"/>'
);
$configurator->BBCodes->addCustom(
	'[link={URL} /]',
	'<a href="{URL}">{URL}</a>'
);

// By default, the filter used for URLs uses a variable called "urlConfig" which points to
// $configurator->urlConfig. Here, we replace this variable with our own instance of UrlConfig
$configurator
	->tags['IMG']
	->attributes['src']
	->filterChain[0]->setVar('urlConfig', $urlConfig);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

// Images should be restricted to i.imgur.com while other links have no restrictions
$text = "[img=http://i.imgur.com/EMD4m1Q.png /]\n"
      . "[img=http://notimgur.example.org/EMD4m1Q.png /]\n"
      . "[link=http://example.org /]";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<img src="http://i.imgur.com/EMD4m1Q.png">
[img=http://notimgur.example.org/EMD4m1Q.png /]
<a href="http://example.org">http://example.org</a>
```
