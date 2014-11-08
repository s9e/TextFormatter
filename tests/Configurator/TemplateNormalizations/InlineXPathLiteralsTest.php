<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineXPathLiterals
*/
class InlineXPathLiteralsTest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<xsl:value-of select="\'Hello\'"/> <xsl:value-of select="\'world\'"/>',
				'Hello world'
			),
			array(
				'<xsl:value-of select="\'&lt;Hello&gt;\'"/>',
				'&lt;Hello&gt;'
			),
			array(
				'<xsl:value-of select="\'&quot;Hello&quot;\'"/>',
				'"Hello"'
			),
			array(
				'Answer is <xsl:value-of select="42"/>.',
				'Answer is 42.'
			),
			array(
				'Answer is <xsl:value-of select=" 00042 "/>.',
				'Answer is 42.'
			),
			array(
				'<hr title="x{&quot;X&quot;}x"/>',
				'<hr title="xXx"/>'
			),
			array(
				'<hr title="x{@X}x"/>',
				'<hr title="x{@X}x"/>'
			),
		);
	}
}