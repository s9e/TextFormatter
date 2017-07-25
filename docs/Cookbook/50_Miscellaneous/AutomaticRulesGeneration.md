## Automatic rules generation

[Automatic rules generation](https://github.com/s9e/TextFormatter/blob/master/docs/RulesGenerators.md) is performed by `$configurator->rulesGenerator`, which you can access as an array.

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
2	s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsCloseFormattingElements
3	s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsFosterFormattingElements
4	s9e\TextFormatter\Configurator\RulesGenerators\DisableAutoLineBreaksIfNewLinesArePreserved
5	s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
6	s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags
7	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
8	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
9	s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
10	s9e\TextFormatter\Configurator\RulesGenerators\TrimFirstLineInCodeBlocks
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
