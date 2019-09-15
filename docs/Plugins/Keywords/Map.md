<h2>How to map keywords to IDs</h2>

Sometimes, keywords are not the best way to identify a resource. For instance, in a tabletop games forum you may want to map the name of spell cards to the name of its image file. This example is inspired by the Mage Wars forum [Arcane Wonders](http://forum.arcanewonders.com/index.php?topic=13249.msg25802).

In the following example, we use the Keywords plugin to capture card names used in normal text. We use one of the [built-in filter](https://github.com/s9e/TextFormatter/blob/master/docs/BuiltInFilters.md) `#hashmap` on the `value` attribute to map the name of the card to its ID. Additionally, we see how additional filters can be used to transform attributes' values. In this instance, we use `strtolower()` to normalize the keyword value to lowercase.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Create a map of every card name with its corresponding file
$cards = [
	"Force Crush"    => 'FWE04',
	"Sunfire Amulet" => 'MWSTX1CKQ01',
	"Wizard's Tower" => 'MWSTX1CKJ02',
	// etc...
];

// Add card names as keywords
foreach ($cards as $name => $id)
{
	// Add the card name as a keyword
	$configurator->Keywords->add($name);
}

// Get a hold of the tag used by the Keywords plugin
$tag = $configurator->Keywords->getTag();

// Set the template used to render cards
$tag->template
	= '<span class="cardPreview" data-cardcode="{@value}">'
	. '<xsl:apply-templates/>'
	. '</span>';

// If you want card names to be case-insensitive, you need to do three things:
// enable it in the plugin, convert the names in $cards to lowercase and set
// the keyword value to be converted to lowercase as well. Of course, if you
// want to keep keywords case-sensitive, this whole block can be skipped
$configurator->Keywords->caseSensitive = false;
$cards = array_change_key_case($cards, CASE_LOWER);
$tag->attributes['value']->filterChain->append('strtolower');

// Here we use the built-in #hashmap filter that will map the keyword's value
// to the corresponding ID
$tag->attributes['value']
	->filterChain
	->append($configurator->attributeFilters->get('#hashmap'))
	->setMap($cards);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "Wizard's Tower and Force Crush";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span class="cardPreview" data-cardcode="MWSTX1CKJ02">Wizard's Tower</span> and <span class="cardPreview" data-cardcode="FWE04">Force Crush</span>
```
