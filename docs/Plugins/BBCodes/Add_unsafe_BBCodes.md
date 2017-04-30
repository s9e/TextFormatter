<h2>Add unsafe BBCodes</h2>

**⚠ Do not create unsafe BBCodes unless you know exactly what you're doing. ⚠**

By default, [unsafe BBCodes are rejected](Synopsis.md#security). However, it is possible to force the plugin to accept an unsafe BBCode by using an `UnsafeTemplate` object for its template.

```php
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;

$templates = [
	'Normal behaviour' => '<script>{TEXT}</script>',
	' Unsafe template' => new UnsafeTemplate('<script>{TEXT}</script>')
];

$configurator = new s9e\TextFormatter\Configurator;
foreach ($templates as $desc => $template)
{
	try
	{
		echo $desc, ': ';
		$configurator->BBCodes->addCustom('[js={TEXT}]', $template);
		echo "The BBCode was added successfully.\n";
	}
	catch (UnsafeTemplateException $e)
	{
		echo $e->getMessage(), ".\n";
	}
}
```
```
Normal behaviour: Attribute 'js' is not properly sanitized to be used in this context.
 Unsafe template: The BBCode was added successfully.
```