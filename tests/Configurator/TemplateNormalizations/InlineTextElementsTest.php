<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineTextElements
*/
class InlineTextElementsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<b><xsl:text>Hello world</xsl:text></b>',
				'<b>Hello world</b>'
			],
			[
				'<b>Hello <xsl:text>world</xsl:text></b>',
				'<b>Hello world</b>'
			],
			[
				'<b><xsl:text>Hello</xsl:text> world</b>',
				'<b>Hello world</b>'
			],
			[
				//<xsl:text> </xsl:text> is not inlined if it would become inter-element whitespace
				'<b>b</b><xsl:text> </xsl:text><i>i</i>',
				'<b>b</b><xsl:text> </xsl:text><i>i</i>'
			],
			[
				//<xsl:text> </xsl:text> is inlined if it is preceded by a text node
				'<b>b</b>Text<xsl:text> </xsl:text><i>i</i>',
				'<b>b</b>Text <i>i</i>'
			],
			[
				//<xsl:text> </xsl:text> is inlined if it is followed by a text node
				'<b>b</b><xsl:text> </xsl:text>Text<i>i</i>',
				'<b>b</b> Text<i>i</i>'
			],
			[
				'<b><xsl:text>Hello</xsl:text><xsl:text> world</xsl:text></b>',
				'<b>Hello world</b>'
			],
			[
				'<b><xsl:text disable-output-escaping="no">AT&amp;T</xsl:text></b>',
				'<b>AT&amp;T</b>'
			],
			[
				'<b><xsl:text disable-output-escaping="yes">AT&amp;T</xsl:text></b>',
				'<b><xsl:text disable-output-escaping="yes">AT&amp;T</xsl:text></b>'
			],
		];
	}
}