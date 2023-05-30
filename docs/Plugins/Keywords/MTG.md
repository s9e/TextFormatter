<h2>Automatically link Magic: The Gathering cards</h2>

```php
$configurator = new s9e\TextFormatter\Configurator;

// Get a list of every card name you want to automatically link
$cards = [
	'Abomination', 'Act of Aggression', 'Armageddon', // etc...
	'Zuran Orb', 'Zuran Spellcaster'
];

foreach ($cards as $card)
{
	// Add the card name as a keyword
	$configurator->Keywords->add($card);

	// BONUS: keywords are case-sensitive but we can add variants of the same
	//        names. Here, we capitalize lowercase words
	if ($card !== ucwords($card))
	{
		$configurator->Keywords->add(ucwords($card));
	}
}

// Define how the names are rendered. Here, as a link to Gatherer
$configurator->Keywords->getTag()->template
	= '<a href="http://gatherer.wizards.com/Pages/Search/Default.aspx?name=+[m/^{@value}$/]"><xsl:apply-templates/></a>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Armageddon and Zuran Orb';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<a href="http://gatherer.wizards.com/Pages/Search/Default.aspx?name=+%5Bm/%5EArmageddon$/%5D">Armageddon</a> and <a href="http://gatherer.wizards.com/Pages/Search/Default.aspx?name=+%5Bm/%5EZuran%20Orb$/%5D">Zuran Orb</a>
```
