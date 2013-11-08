## Introduction

s9e\TextFormatter uses [XSLT 1.0](http://www.w3.org/TR/xslt) as its templating language. A template can be made of any XSL that would be valid in an `xsl:template` element. If it doesn't contain any XSL-specific markup, a template can also be defined using plain HTML, which is transparently converted to XSL. Note that most plugins accept other syntaxes and transparently convert them to XSL. For instance, the BBCodes plugin accepts templates in [its own syntax](https://github.com/s9e/TextFormatter/blob/master/docs/BBCodeMonkey.md) while the [Generic plugin](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Generic) allows PCRE-style replacements.

Tags can have any number of templates. A tag's templates collection is accessible as an array in `$tag->templates` where keys are XPath predicates and values are templates. A template with an empty predicate is the tag's default template, which can also be accessed as `$tag->defaultTemplate` for convenience.

### Create multiple templates for the same tag

In the following example, we load the default [QUOTE] BBCode and we set its default template to have a class named "odd". Then we add another template with a class named "even" if the number of QUOTE ancestors is odd. See [XPath axes](http://www.w3.org/TR/xpath/#location-paths) for a list of similar XPath expressions.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Load the default [QUOTE] BBCode
$configurator->BBCodes->addFromRepository('QUOTE');

// Get a hold of the QUOTE tag
$tag = $configurator->tags['QUOTE'];

// Optional: we can remove all templates with clear() to start with a clean slate
$tag->templates->clear();

// Create a default template for B
$tag->defaultTemplate
	= '<blockquote class="odd"><xsl:apply-templates/></blockquote>';

// Add a template with predicate "count(ancestor::QUOTE) mod 2"
$tag->templates['count(ancestor::QUOTE) mod 2']
	= '<blockquote class="even"><xsl:apply-templates/></blockquote>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[quote][quote][quote]...[/quote][/quote][/quote]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<blockquote class="odd"><blockquote class="even"><blockquote class="odd">...</blockquote></blockquote></blockquote>
```
