<?php

include __DIR__ . '/../../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;

$configurator->tags->add(
	'A',
	[
		'attributes' => [
			'href' => [
				'filterChain' => ['#url'],
				'required'    => true
			]
		],
		'template' => '<a href="{@href}"><xsl:apply-templates/></a>'
	]
);

$configurator->BBCodes->add(
	'A',
	[
		'contentAttributes' => ['href'],
		'defaultAttribute'  => 'href'
	]
);


//==============================================================================

extract($configurator->finalize());

$text = "[a]http://example.org[/a]\n"
      . "[a=http://example.org]site[/a]";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n";

// Outputs:
//
// <a href="http://example.org">http://example.org</a>
// <a href="http://example.org">site</a>
