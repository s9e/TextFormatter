<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeAttributeNames
*/
class NormalizeAttributeNamesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div><xsl:attribute name="CLASS">foo</xsl:attribute><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo</xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<b ID="FOO"/>',
				'<b id="FOO"/>'
			],
			[
				'<div><xsl:attribute name="{@NAME}">foo</xsl:attribute><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="{@NAME}">foo</xsl:attribute><xsl:apply-templates/></div>'
			],
		];
	}
}