<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineTextElements
*/
class InlineTextElementsTest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<b><xsl:text>Hello world</xsl:text></b>',
				'<b>Hello world</b>'
			),
			array(
				'<b>Hello <xsl:text>world</xsl:text></b>',
				'<b>Hello world</b>'
			),
			array(
				'<b><xsl:text>Hello</xsl:text> world</b>',
				'<b>Hello world</b>'
			),
			array(
				//<xsl:text> </xsl:text> is not inlined if it would become inter-element whitespace
				'<b>b</b><xsl:text> </xsl:text><i>i</i>',
				'<b>b</b><xsl:text> </xsl:text><i>i</i>'
			),
			array(
				//<xsl:text> </xsl:text> is inlined if it is preceded by a text node
				'<b>b</b>Text<xsl:text> </xsl:text><i>i</i>',
				'<b>b</b>Text <i>i</i>'
			),
			array(
				//<xsl:text> </xsl:text> is inlined if it is followed by a text node
				'<b>b</b><xsl:text> </xsl:text>Text<i>i</i>',
				'<b>b</b> Text<i>i</i>'
			),
			array(
				'<b><xsl:text>Hello</xsl:text><xsl:text> world</xsl:text></b>',
				'<b>Hello world</b>'
			),
		);
	}
}