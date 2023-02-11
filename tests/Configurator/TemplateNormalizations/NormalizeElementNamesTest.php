<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeElementNames
*/
class NormalizeElementNamesTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<hr/>',
				'<hr/>'
			],
			[
				'<B><BR/></B>',
				'<b><br/></b>'
			],
			[
				'<B id="b"><I id="i"><U id="u">x</U></I></B>',
				'<b id="b"><i id="i"><u id="u">x</u></i></b>'
			],
			[
				'<SVG xmlns="http://www.w3.org/2000/svg"/>',
				'<svg xmlns="http://www.w3.org/2000/svg"/>'
			],
			[
				'<SVG xmlns="http://www.w3.org/2000/svg"><TEXT/></SVG>',
				'<svg xmlns="http://www.w3.org/2000/svg"><text/></svg>'
			],
			[
				'<xsl:element name="B"><BR/></xsl:element>',
				'<xsl:element name="b"><br/></xsl:element>'
			],
			[
				'<xsl:element name="{@NAME}"/>',
				'<xsl:element name="{@NAME}"/>'
			],
		];
	}
}