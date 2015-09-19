#!/usr/bin/php
<?php

$dirpath = realpath(__DIR__ . '/../tests/Configurator/Helpers/data/TemplateParser/');

$void  = ['Y' => 'hr', 'M' => '{local-name()}', 'N' => 'div'];
$empty = [
	'Y' => ['', ''],
	'M' => ['<xsl:apply-templates/>', '<applyTemplates/>'],
	'N' => ['foo', '<output escape="text" type="literal">foo</output>']
];

$voidAttr = [
	'Y' => ' void="yes"',
	'M' => ' void="maybe"',
	'N' => ''
];

$emptyAttr = [
	'Y' => ' empty="yes"',
	'M' => ' empty="maybe"',
	'N' => ''
];

$i = 100;
foreach ($void as $iVoid => $elName)
{
	foreach ($empty as $iEmpty => $pair)
	{
		list($xslContent, $xmlContent) = $pair;

		$template = '<xsl:element name="' . $elName . '"><xsl:attribute name="id">foo</xsl:attribute>' . $xslContent . '</xsl:element>';

		$xml =
			'<template>
			  <element name="' . $elName . '" id="1"' . $voidAttr[$iVoid] . $emptyAttr[$iEmpty] . '>
			    <attribute name="id">
			      <output escape="attribute" type="literal">foo</output>
			    </attribute>
			    <closeTag id="1"/>';

		// Content of void elements is removed in HTML mode
		if ($iVoid !== 'Y' && $xmlContent !== '')
		{
			$xml .= "\n\t\t\t    " . $xmlContent;
		}

		$xml .= '
			  </element>
			</template>';

		// Remove the extra indentation
		$xml = str_replace("\n\t\t\t", "\n", $xml);
		$template = str_replace("\n\t\t\t", "\n", $template);

		// Save the result
		file_put_contents($dirpath . '/' . $i . '.template', $template);
		file_put_contents($dirpath . '/' . $i . '.xml', $xml);

		++$i;
	}
}

die("Done.\n");