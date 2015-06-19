<h2>Use template parameters in a BBCode template</h2>

As for localization, when creating a custom BBCode, any token that is not associated with a filter is presumed to be a template parameter. In the following example, we create a BBCode that only outputs its content if a parameter named `S_LOGGED_IN` is true.

Note that the BBCodes plugin automatically creates template parameters for you.

```php
// We'll create a fake user object for this example
$user = new stdClass;
$user->isLoggedIn = true;

$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('COLOR');
$configurator->BBCodes->addCustom(
	'[noguests]{ANYTHING}[/noguests]',
	'<xsl:choose>
		<xsl:when test="$S_LOGGED_IN">
			<div>{ANYTHING}</div>
		</xsl:when>
		<xsl:otherwise>
			<div>Only registered users can read this content</div>
		</xsl:otherwise>
	</xsl:choose>'
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[noguests]Some [color=red]top secret[/color] info[/noguests]';
$xml  = $parser->parse($text);

// Set up the values before rendering
$renderer->setParameter('S_LOGGED_IN', $user->isLoggedIn);

// Render the text
echo $renderer->render($xml);
```
```html
<div>Some <span style="color:red">top secret</span> info</div>
```
