<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeElementNames
*/
class NormalizeElementNamesTest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<hr/>',
				'<hr/>'
			),
			array(
				'<B><BR/></B>',
				'<b><br/></b>'
			),
			array(
				'<B id="b"><I id="i"><U id="u">x</U></I></B>',
				'<b id="b"><i id="i"><u id="u">x</u></i></b>'
			),
			array(
				'<SVG xmlns="http://www.w3.org/2000/svg"/>',
				'<svg xmlns="http://www.w3.org/2000/svg"/>'
			),
			array(
				'<SVG xmlns="http://www.w3.org/2000/svg"><TEXT/></SVG>',
				'<svg xmlns="http://www.w3.org/2000/svg"><text/></svg>'
			),
			array(
				'<xsl:element name="B"><BR/></xsl:element>',
				'<xsl:element name="b"><br/></xsl:element>'
			),
			array(
				'<xsl:element name="{@NAME}"/>',
				'<xsl:element name="{@NAME}"/>'
			),
		);
	}
}