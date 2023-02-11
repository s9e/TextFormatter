<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\TransposeComments
*/
class TransposeCommentsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<!-- This is a comment -->hi<!-- That one too -->',
				'<xsl:comment> This is a comment </xsl:comment>hi<xsl:comment> That one too </xsl:comment>'
			],
			[
				'<!-- A&B -->',
				'<xsl:comment> A&amp;B </xsl:comment>'
			],
		];
	}
}