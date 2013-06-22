#!/usr/bin/php
<?php

$dirpath = realpath(__DIR__ . '/../tests/Configurator/Helpers/data/TemplateParser/');

$modes = ['H' => 'html', 'X' => 'xml'];
$void  = ['Y' => 'hr', 'M' => '{local-name()}', 'N' => 'div'];
$empty = [
	'Y' => ['', ''],
	'M' => ['<xsl:apply-templates/>', '<applyTemplates/>'],
	'N' => ['foo', '<output type="literal">foo</output>']
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
foreach ($modes as $iMode => $mode)
{
	foreach ($void as $iVoid => $elName)
	{
		foreach ($empty as $iEmpty => $pair)
		{
			list($xslContent, $xmlContent) = $pair;

			// Content of void elements is removed in HTML mode
			if ($iMode . $iVoid === 'HY')
			{
				$xmlContent = '';
			}

			$xsl = '<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="' . $mode . '" encoding="utf-8" />

	<!-- ' . $iVoid . $iEmpty . $iMode . ' -->
	<xsl:template match="FOO">
		<xsl:element name="' . $elName . '"><xsl:attribute name="id">foo</xsl:attribute>' . $xslContent . '</xsl:element>
	</xsl:template>

</xsl:stylesheet>';

			$xml = '<stylesheet outputMethod="' . $mode . '">
	<template>
		<match priority="0">FOO</match>
		<element name="' . $elName . '" id="1"' . $voidAttr[$iVoid] . $emptyAttr[$iEmpty] . '>
			<attribute name="id">
				<output type="literal">foo</output>
			</attribute>
			<closeTag id="1"/>
			' . $xmlContent . '
		</element>
	</template>
</stylesheet>';

			file_put_contents($dirpath . '/' . $i . '.xsl', $xsl);
			file_put_contents($dirpath . '/' . $i . '.xml', $xml);

			++$i;
		}
	}
}