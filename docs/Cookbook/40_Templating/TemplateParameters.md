## Template parameters

Template parameters come from [XSLT](http://www.w3.org/TR/xslt#variables). They are a special kind of global variables, shared among all templates. They have to be created during configuration, they can take a default value and they are always interpreted as text (special characters are automatically escaped.) They can be used for localization, or passing some dynamic information before rendering.

### How to use template parameters

In the following example, we use the BBCodes plugin to create a BBCode that outputs the value of the parameter "username" (expressed as `$username` in the template.)

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom('[you]', '<xsl:value-of select="$username"/>');

// Create the template parameter "username" with default value "you"
$configurator->rendering->parameters['username'] = 'you';

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

### How to get a list of template parameters in use

If you let the end user define their own templates (for custom BBCodes for example) you may not always know in advance what template parameters are in use. And since you have to set their value before rendering, you need to get their names. This can be done with `$configurator->rendering>getAllParameters()`, which will return the names and default values of all the template parameters that have been created and/or are in use.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[noguests]{TEXT}[/noguests]',
	'<xsl:if test="$S_LOGGED_IN=1">{TEXT}</xsl:if>'
);

// Get the list of parameters, which you should probably save with the renderer
// because you'll need it at rendering time
$parameters = $configurator->rendering->getAllParameters();

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Are you are logged in? [noguests]Yes you are.[/noguests]';
$xml  = $parser->parse($text);

// First with no value
echo $renderer->render($xml), "\n";

// Then with a value
if (isset($parameters['S_LOGGED_IN']))
{
	$renderer->setParameter('S_LOGGED_IN', true);
}

echo $renderer->render($xml);
```
```html
Are you are logged in? 
Are you are logged in? Yes you are.
```
