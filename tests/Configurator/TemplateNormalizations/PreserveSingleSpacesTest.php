<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\PreserveSingleSpaces
*/
class PreserveSingleSpacesTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<b>foo</b> <i>bar</i>',
				'<b>foo</b><xsl:text> </xsl:text><i>bar</i>'
			],
			[
				'<b>foo</b><xsl:text> </xsl:text><i>bar</i>',
				'<b>foo</b><xsl:text> </xsl:text><i>bar</i>'
			],
		];
	}
}