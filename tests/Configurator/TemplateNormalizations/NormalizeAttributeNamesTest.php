<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeAttributeNames
*/
class NormalizeAttributeNamesTest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<div><xsl:attribute name="CLASS">foo</xsl:attribute><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo</xsl:attribute><xsl:apply-templates/></div>'
			),
			array(
				'<b ID="FOO"/>',
				'<b id="FOO"/>'
			),
		);
	}
}