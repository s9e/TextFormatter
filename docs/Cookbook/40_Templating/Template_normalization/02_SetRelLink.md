## Automatically set `rel="nofollow"` on every link

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->templateNormalizer->append(
	function (DOMNode $template)
	{
		foreach ($template->getElementsByTagName('a') as $a)
		{
			$a->setAttribute('rel', 'nofollow');
		}
	}
);

echo $configurator->templateNormalizer->normalizeTemplate(
	'<a href="{@url}"><xsl:apply-templates/></a>'
);
```
```xslt
<a href="{@url}" rel="nofollow"><xsl:apply-templates/></a>
```
