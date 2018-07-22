<h2>Change the default settings</h2>

By default, template normalization consists in optimizing a template's content by removing superfluous whitespace and inlining content wherever possible, as well as normalize HTML elements' and attributes' names to lowercase and [other menial tasks](https://github.com/s9e/TextFormatter/tree/master/src/Configurator/TemplateNormalizations).

Template normalization is performed by `$configurator->templateNormalizer`, which you can access as an array.

```php
$configurator = new s9e\TextFormatter\Configurator;

foreach ($configurator->templateNormalizer as $i => $normalizer)
{
	echo $i, "\t", get_class($normalizer), "\n";
}
```
```
0	s9e\TextFormatter\Configurator\TemplateNormalizations\PreserveSingleSpaces
1	s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveComments
2	s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveInterElementWhitespace
3	s9e\TextFormatter\Configurator\TemplateNormalizations\FixUnescapedCurlyBracesInHtmlAttributes
4	s9e\TextFormatter\Configurator\TemplateNormalizations\UninlineAttributes
5	s9e\TextFormatter\Configurator\TemplateNormalizations\EnforceHTMLOmittedEndTags
6	s9e\TextFormatter\Configurator\TemplateNormalizations\FoldArithmeticConstants
7	s9e\TextFormatter\Configurator\TemplateNormalizations\FoldConstantXPathExpressions
8	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineCDATA
9	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineElements
10	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineTextElements
11	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineXPathLiterals
12	s9e\TextFormatter\Configurator\TemplateNormalizations\MinifyXPathExpressions
13	s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeAttributeNames
14	s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeElementNames
15	s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeUrls
16	s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeConditionalAttributes
17	s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeConditionalValueOf
18	s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChoose
19	s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChooseText
20	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineAttributes
21	s9e\TextFormatter\Configurator\TemplateNormalizations\InlineInferredValues
22	s9e\TextFormatter\Configurator\TemplateNormalizations\SetRelNoreferrerOnTargetedLinks
23	s9e\TextFormatter\Configurator\TemplateNormalizations\MinifyInlineCSS
```

### Remove a normalization

```php
$configurator = new s9e\TextFormatter\Configurator;

echo $configurator->templateNormalizer->normalizeTemplate('<![CDATA[ Will be inlined ]]>'), "\n";

$configurator->templateNormalizer->remove('InlineCDATA');

echo $configurator->templateNormalizer->normalizeTemplate('<![CDATA[ Will not be inlined ]]>');
```
```html
 Will be inlined 
<![CDATA[ Will not be inlined ]]>
```

### Add your own custom normalization

You can `append()` or `prepend()` a callback to the template normalizer. It will be called with one argument, a `DOMNode` that represents the `<xsl:template/>` element that contains the template, which you can modify normally. At the end, the node is serialized back to XML. The template normalizer iterates through the list of normalizations up to 5 times, until none of them modifies the template. If you set `onlyOnce` to true, the normalization will only be applied during the first loop.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add a callback that adds a "?" to the template and that is executed only once
$configurator->templateNormalizer->append(
	function (DOMNode $template)
	{
		$template->appendChild($template->ownerDocument->createTextNode('?'));
	}
)->onlyOnce = true;

// Add a callback that adds a "!" to the template
$configurator->templateNormalizer->append(
	function (DOMNode $template)
	{
		$template->appendChild($template->ownerDocument->createTextNode('!'));
	}
);

echo $configurator->templateNormalizer->normalizeTemplate('Hello world');
```
```html
Hello world?!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
```
