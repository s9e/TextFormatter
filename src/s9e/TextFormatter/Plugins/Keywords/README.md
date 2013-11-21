## Synopsis

This plugin serves to capture keywords in plain text and render them as a rich element of your choosing such as a link, a popup or a widget.

Examples of use cases:

  * [Card names from Magic: The Gathering](https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook/30_Plugins/Keywords/MTG.md) or Hearthstone
  * Heroes names and abilities from League of Legends or Dota 2

This plugin can handle an arbitrary large number of keywords. As a rule of thumb, on PHP 5.5 with OPcache running on commodity hardware it takes about 1.5 ms to search for the names of 13,638 Magic: The Gathering cards in a text of a thousand characters. However, performance is highly dependent on the list of keywords and the content of the text and it is recommended to benchmark your application accordingly.

Keywords are case-sensitive by default.

## When *not* to use this plugin

In some cases, it might be preferable to use the [Generic](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Generic) plugin. For instance, in a programming forum, instead of creating a list of every function name as keywords (in order to link them to a manual, for example) you may want to use the generic plugin and simply capture any series of valid characters immediately followed by a parenthesis.

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add a couple of keywords
$configurator->Keywords->add('Bulbasaur');
$configurator->Keywords->add('Pikachu');

// Keywords are case-sensitive by default but you can make case-insensitive.
// This is not recommended if the list of keywords contain words that could
// appear in normal speech, e.g. "Fire", "Air", "The"
$configurator->Keywords->caseSensitive = false;

// Set the template that renders them
$configurator->Keywords->getTag()->defaultTemplate
	= '<a href="http://bulbapedia.bulbagarden.net/wiki/{@value}"><xsl:apply-templates/></a>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Bulbasaur and Pikachu';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://en.wikipedia.org/wiki/Bulbasaur">Bulbasaur</a> and <a href="http://en.wikipedia.org/wiki/Pikachu">Pikachu</a>
```
