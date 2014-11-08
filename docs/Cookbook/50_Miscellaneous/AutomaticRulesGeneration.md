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
2	s9e\TextFormatter\Configurator\RulesGenerators\DisableAutoLineBreaksIfNewLinesArePreserved
3	s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
4	s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags
5	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
6	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
7	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
</pre>

### Remove a generator

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->rulesGenerator->remove('IgnoreTextIfDisallowed');
```

### Add a default generator

To add the `ManageParagraphs` generator:
```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->rulesGenerator->add('ManageParagraphs');
```
