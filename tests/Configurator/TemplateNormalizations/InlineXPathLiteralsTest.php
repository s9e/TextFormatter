<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineXPathLiterals
*/
class InlineXPathLiteralsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<xsl:value-of select="\'Hello\'"/> <xsl:value-of select="\'world\'"/>',
				'Hello world'
			],
			[
				'<xsl:value-of select="\'&lt;Hello&gt;\'"/>',
				'&lt;Hello&gt;'
			],
			[
				'<xsl:value-of select="\'&quot;Hello&quot;\'"/>',
				'"Hello"'
			],
			[
				'Answer is <xsl:value-of select="42"/>.',
				'Answer is 42.'
			],
			[
				'Answer is <xsl:value-of select="4.2"/>.',
				'Answer is 4.2.'
			],
			[
				'Answer is <xsl:value-of select=" 00042 "/>.',
				'Answer is 42.'
			],
			[
				'<hr title="x{&quot;X&quot;}x"/>',
				'<hr title="xXx"/>'
			],
			[
				'<hr title="x{@X}x"/>',
				'<hr title="x{@X}x"/>'
			],
			[
				'<b title="{\'}}}\'}"><xsl:apply-templates/></b>',
				'<b title="}}}}}}"><xsl:apply-templates/></b>'
			],
			[
				'<b><xsl:attribute name="title"><xsl:value-of select="\'}}}\'"/></xsl:attribute><xsl:apply-templates/></b>',
				'<b><xsl:attribute name="title">}}}</xsl:attribute><xsl:apply-templates/></b>'
			],
		];
	}
}