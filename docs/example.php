<?php

include __DIR__ . '/../src/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;

// Add a BBCode from the default repository src/Plugins/BBCodes/Configurator/repository.xml
$configurator->BBCodes->addFromRepository('QUOTE');

// Add a few BBCodes using their human readable representation. You can check out repository.xml for
// a more advanced [LIST] BBCode
$configurator->BBCodes->addCustom('[B]{TEXT}[/B]',   '<b>{TEXT}</b>');
$configurator->BBCodes->addCustom('[UL]{TEXT}[/UL]', '<ul>{TEXT}</ul>');
$configurator->BBCodes->addCustom('[LI]{TEXT}[/LI]', '<li>{TEXT}</li>');

// Add a BBCode using the verbose API
$configurator->BBCodes->add('I');
$tag = $configurator->tags->add('I');
$tag->defaultTemplate = '<i><xsl:apply-templates/></i>';

// Add a URL BBCode, and use it for magic links too. Note that
$configurator->BBCodes->addCustom(
	'[URL={URL;useContent}]{TEXT}[/URL]',
	'<a href="{URL}">{TEXT}</a>'
);

// When we created the [URL] BBCode, it automatically created a general purpose URL tag which can
// be used by other plugins. Here we load the Autolink plugin and specify which tag to use. In this
// case, it's redundant because "URL" is the default value for Autolink's tags. Also, if the tag
// does not exist when Autolink is loaded, a default tag will be created
$configurator->plugins->load('Autolink', array('tagName' => 'URL'));

// Add a couple of censored words, one with a custom replacement
$configurator->Censor->add('apple*');
$configurator->Censor->add('bananas', 'oranges');

// Add a couple of emoticons
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" />');
$configurator->Emoticons->add(':(', '<img src="sad.png" alt=":(" />');

// We'll also allow a bit of HTML. Specifically, <a> elements and HTML entities
$configurator->HTMLElements->allowElement('a');
$configurator->HTMLElements->allowAttribute('a', 'href');
$configurator->plugins->load('HTMLEntities');

// We'll disallow links to example.org, which will automagically apply to [URL] and <a>
$configurator->urlConfig->disallowHost('example.org');

// Finally, instead of having to explicitly define what tag is allowed where and how, we'll let the
// configurator define a bunch of rules based on HTML5
$configurator->addHTML5Rules();

// ...or uncomment the following for a quick look at what rules would be created
print_r(s9e\TextFormatter\Configurator\Helpers\HTML5\RulesGenerator::getRules($configurator->tags));

//==============================================================================

// Done with configuration, we save the parser and its renderer so they can be reused later
file_put_contents('/tmp/parser.txt',   serialize($configurator->getParser()));
file_put_contents('/tmp/renderer.txt', serialize($configurator->getRenderer()));