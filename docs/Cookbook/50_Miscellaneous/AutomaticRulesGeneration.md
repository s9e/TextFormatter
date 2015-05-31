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
2	s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsFosterFormattingElements
3	s9e\TextFormatter\Configurator\RulesGenerators\DisableAutoLineBreaksIfNewLinesArePreserved
4	s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
5	s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags
6	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
7	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
8	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
9	s9e\TextFormatter\Configurator\RulesGenerators\TrimFirstLineInCodeBlocks
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
