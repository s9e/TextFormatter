## Automatic rules generation

Automatic rules generation is performed by `$configurator->rulesGenerator`, which you can access as an array.

```php
$configurator = new s9e\TextFormatter\Configurator;

foreach ($configurator->rulesGenerator as $i => $generator)
{
	echo $i, "\t", get_class($generator), "\n";
}
```
<pre>
0	s9e\TextFormatter\Configurator\RulesGenerators\AutoCloseIfVoid
1	s9e\TextFormatter\Configurator\RulesGenerators\AutoReopenFormattingElements
2	s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
3	s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags
4	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
5	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
6	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
7	s9e\TextFormatter\Configurator\RulesGenerators\NoBrIfWhitespaceIsPreserved
</pre>

### Remove a generator

```php
$configurator = new s9e\TextFormatter\Configurator;

$i = $configurator->rulesGenerator->indexOf('IgnoreTextIfDisallowed');
unset($configurator->rulesGenerator[$i]);
```

### Add a default generator

To add the `ManageParagraphs` generator:
```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->rulesGenerator->append('ManageParagraphs');
```
