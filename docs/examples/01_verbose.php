<?php

include __DIR__ . '/../../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;

// Add some BBCodes from the default repository that you can find in
// ../src/Plugins/BBCodes/Configurator/repository.xml
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('U');
$configurator->BBCodes->addFromRepository('S');
$configurator->BBCodes->addFromRepository('COLOR');
$configurator->BBCodes->addFromRepository('URL');
$configurator->BBCodes->addFromRepository('EMAIL');
$configurator->BBCodes->addFromRepository('CODE');
$configurator->BBCodes->addFromRepository('QUOTE');
$configurator->BBCodes->addFromRepository('LIST');
$configurator->BBCodes->addFromRepository('*');
$configurator->BBCodes->addFromRepository('SPOILER');

// Add custom [size] BBCode which forces values to be between 8px and 36px
$configurator->BBCodes->addCustom(
	'[size={RANGE=8,36}]{TEXT}[/size]',
	'<span style="font-size:{RANGE}px">{TEXT}</span>'
);

// NOTE: trying to add unsafe BBCodes results in an UnsafeTemplateException being thrown
//$configurator->BBCodes->addCustom('[BAD={TEXT1}]{TEXT2}[/BAD]', '<a href="{TEXT1}">{TEXT2}</a>');
//$configurator->BBCodes->addCustom('[BAD={TEXT1}]{TEXT2}[/BAD]', '<b onblur="{TEXT1}">{TEXT2}</b>');
//$configurator->BBCodes->addCustom('[BAD={TEXT1}]{TEXT2}[/BAD]', '<b style="{TEXT1}">{TEXT2}</b>');
//$configurator->BBCodes->addCustom('[BAD]{TEXT}[/BAD]',          '<script>{TEXT}"</script>');
//$configurator->BBCodes->addCustom('[BAD]{TEXT}[/BAD]',          '<style>{TEXT}"</script>');

// Add a couple of censored words, one with a custom replacement
$configurator->Censor->add('apple*');
$configurator->Censor->add('bananas', 'oranges');

// Add a couple of emoticons. You can specify any HTML or XSLT to represent them. Here we create a
// couple of emoticons mapped to Unicode characters, and one mapped to an image. You could map them
// to spritesheets or whatever else
$configurator->Emoticons->add(':)', '&#x263A;');
$configurator->Emoticons->add(':(', '&#x263B;');
$configurator->Emoticons->add(':lol:', '<img src="/path/to/lol.png" alt=":lol:"/>');

// We'll also allow a bit of HTML. Specifically, <a> elements with a non-optional href attribute and
// HTML entities
$configurator->HTMLElements->allowElement('a');
$configurator->HTMLElements->allowAttribute('a', 'href')->required = true;

// Automatically linkify URLs in plain text with the Autolink plugin, and email addresses with the
// Autoemail plugin
$configurator->Autoemail;
$configurator->Autolink;

//==============================================================================

// Done with configuration, now we create a parser and its renderer
extract($configurator->finalize());

// The parser and renderer should be cached somewhere so we don't have recreate them every time
//file_put_contents('/tmp/parser.txt',   serialize($parser));
//file_put_contents('/tmp/renderer.txt', serialize($renderer));

// Parse a simple message then render it as HTML. The XML result is what you save in your database
// while the HTML rendering is what you show to the users
$text = "Hello, [i]world[/i] :)\nFind more examples in the [url=https://s9etextformatter.readthedocs.io/]documentation[/url].";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n";

// Outputs:
//
// Hello, <i>world</i> ☺
// Find more examples in the <a href="https://s9etextformatter.readthedocs.io/">documentation</a>.
