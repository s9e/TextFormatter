This plugin serves to capture keywords in plain text and render them as a rich element of your choosing such as a link, a popup or a widget.

Examples of use cases:

  * [Card names from Magic: The Gathering](MTG.md) or Hearthstone *(concept known as "autocard")*
  * Heroes names and abilities from League of Legends or Dota 2

This plugin can handle an arbitrary large number of keywords. As a rule of thumb, on PHP 5.5 with OPcache running on commodity hardware it takes about 1.5 ms to search for the names of 13,638 Magic: The Gathering cards in a text of a thousand characters. However, performance is highly dependent on the list of keywords and the content of the text and it is recommended to benchmark your application accordingly.

Keywords are case-sensitive by default.

### When *not* to use this plugin

In some cases, it might be preferable to use the [Preg](../Preg/Synopsis.md) plugin. For instance, in a programming forum, instead of creating a list of every function name as keywords (in order to link them to a manual, for example) you may want to use the Preg plugin and simply capture any series of valid characters immediately followed by a parenthesis.

## Examples

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
$configurator->Keywords->getTag()->template
	= '<a href="http://bulbapedia.bulbagarden.net/wiki/{@value}"><xsl:apply-templates/></a>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Bulbasaur and Pikachu';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://bulbapedia.bulbagarden.net/wiki/Bulbasaur">Bulbasaur</a> and <a href="http://bulbapedia.bulbagarden.net/wiki/Pikachu">Pikachu</a>
```

### How to only capture the first occurence of each keyword

By default, every occurence of every keyword is captured by the plugin. In situations where keywords are used at a high frequency, this can create unwanted noise. In this case, you can configure the Keywords plugin to only capture the first occurence of each keyword by setting the `onlyFirst` property.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add a couple of keywords
$configurator->Keywords->add('Pikachu');
$configurator->Keywords->add('Raichu');

// Set onlyFirst so that we only link the first occurence of each keyword
$configurator->Keywords->onlyFirst = true;

// Set the template that renders them
$configurator->Keywords->getTag()->template
	= '<a href="http://bulbapedia.bulbagarden.net/wiki/{@value}"><xsl:apply-templates/></a>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'My Pikachu is the best Pikachu. Raichu sucks.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
My <a href="http://bulbapedia.bulbagarden.net/wiki/Pikachu">Pikachu</a> is the best Pikachu. <a href="http://bulbapedia.bulbagarden.net/wiki/Raichu">Raichu</a> sucks.
```
