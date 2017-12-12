<h2>Template parameters</h2>

Template parameters come from [XSLT](https://www.w3.org/TR/xslt#variables). They are a special kind of global variables, shared among all templates. They have to be created during configuration, they can take a default value and they are always interpreted as text (special characters are automatically escaped.) They can be used for localization, or passing some dynamic information before rendering.

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

During configuration, you can access the list of template parameters that have been defined via `$configurator->rendering->parameters`. But if you let the end user define their own templates (for custom BBCodes for example) they might be trying to use parameters that you haven't defined. You can get a list of all parameters (both defined and used in templates) with `$configurator->rendering->getAllParameters()`.

Before and during rendering, you can get the list of parameters via `$renderer->getParameters()`.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[noguests]{TEXT}[/noguests]',
	'<xsl:if test="$S_LOGGED_IN=1">{TEXT}</xsl:if>'
);

// Get a list of all parameters during configuration
echo "During configuration\n--------------------\n";
print_r($configurator->rendering->getAllParameters());
echo "\n";

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Are you are logged in? [noguests]Yes you are.[/noguests]';
$xml  = $parser->parse($text);

echo "\nDuring rendering\n----------------\n";

// First with no value
echo 'Result with S_LOGGED_IN=', $renderer->getParameter('S_LOGGED_IN'), "\n";
echo $renderer->render($xml), "\n\n";

// Then with a value
$renderer->setParameter('S_LOGGED_IN', true);
echo 'Result with S_LOGGED_IN=', $renderer->getParameter('S_LOGGED_IN'), "\n";
echo $renderer->render($xml);
```
```html
During configuration
--------------------
Array
(
    [S_LOGGED_IN] => 
)


During rendering
----------------
Result with S_LOGGED_IN=
Are you are logged in? 

Result with S_LOGGED_IN=1
Are you are logged in? Yes you are.
```
