## Stylesheet parameters

Stylesheet parameters come from [XSLT](http://www.w3.org/TR/xslt#variables). They are a special kind of global variables, shared among all templates. They have to be created during configuration, they can take a default value and they are always interpreted as text (special characters are automatically escaped.) They can be used for localization, or passing some dynamic information before rendering.

### How to use stylesheet parameters

In the following example, we use the BBCodes plugin to create a BBCode that outputs the value of the parameter "username" (expressed as `$username` in the template.)

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom('[you]', '<xsl:value-of select="$username"/>');

// Create the stylesheet parameter "username" with default value "you"
$configurator->stylesheet->parameters['username'] = 'you';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Hello [you]';
$xml  = $parser->parse($text);

// Let's see how the text is rendered if "username" is not set
echo $renderer->render($xml), "\n";

// Now set a value for "username" and see how the text is rendered
$renderer->setParameter('username', 'Joe123');

echo $renderer->render($xml);
```
```html
Hello you
Hello Joe123
```

### How to get a list of stylesheet parameters in use

If you let the end user define their own templates (for custom BBCodes for example) you may not always know in advance what stylesheet parameters are in use. And since you have to set their value before rendering, you need to get their names. This can be done with `$configurator->stylesheet->getUsedParameters()`, which will return the names and default values of all the stylesheet parameters that have been created and/or are in use.

Note that the values returned are expressed in XPath, meaning that the string `foo` (without quotes) is returned as `'foo'` (in quotes.)

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[noguests]{TEXT}[/noguests]',
	'<xsl:if test="$S_LOGGED_IN=1">{TEXT}</xsl:if>'
);

// Get the list of parameters, which you should probably save with the renderer
// because you'll need it at rendering time
$usedParameters = $configurator->stylesheet->getUsedParameters();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Are you are logged in? [noguests]Yes you are.[/noguests]';
$xml  = $parser->parse($text);

// First with no value
echo $renderer->render($xml), "\n";

// Then with a value
if (isset($usedParameters['S_LOGGED_IN']))
{
	$renderer->setParameter('S_LOGGED_IN', true);
}

echo $renderer->render($xml);
```
```html
Are you are logged in? 
Are you are logged in? Yes you are.
```
