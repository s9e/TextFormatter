<h2>Automatic rules generation</h2>

Automatic rules generation is performed by `$configurator->rulesGenerator`, which you can access as an array.

See [Rules generators](Rules_generators.md) for a description of rules generators.

```php
$configurator = new s9e\TextFormatter\Configurator;

foreach ($configurator->rulesGenerator as $i => $generator)
{
	echo $i, "\t", get_class($generator), "\n";
}
```
```
0	s9e\TextFormatter\Configurator\RulesGenerators\AutoCloseIfVoid
1	s9e\TextFormatter\Configurator\RulesGenerators\AutoReopenFormattingElements
2	s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsCloseFormattingElements
3	s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsFosterFormattingElements
4	s9e\TextFormatter\Configurator\RulesGenerators\DisableAutoLineBreaksIfNewLinesArePreserved
5	s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
6	s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags
7	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
8	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
9	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
10	s9e\TextFormatter\Configurator\RulesGenerators\TrimFirstLineInCodeBlocks
```

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
