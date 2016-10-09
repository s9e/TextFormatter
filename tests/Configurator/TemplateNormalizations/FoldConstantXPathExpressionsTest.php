<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\FoldConstantXPathExpressions
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractConstantFolding
*/
class FoldConstantXPathExpressionsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<xsl:value-of select="1 + 1"/>',
				'<xsl:value-of select="\'2\'"/>'
			],
			[
				'<xsl:value-of select="concat(\'foo\', \'bar\')"/>',
				'<xsl:value-of select="\'foobar\'"/>'
			],
			[
				'<xsl:value-of select="foo"/>',
				'<xsl:value-of select="foo"/>'
			],
			[
				'<xsl:value-of select="FOO"/>',
				'<xsl:value-of select="FOO"/>'
			],
			[
				'<xsl:value-of select="@foo"/>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:value-of select="$foo"/>',
				'<xsl:value-of select="$foo"/>'
			],
			[
				'<xsl:value-of select="."/>',
				'<xsl:value-of select="."/>'
			],
			[
				'<xsl:value-of select="1.5 + 1.5"/>',
				'<xsl:value-of select="\'3\'"/>'
			],
			[
				'<xsl:value-of select="3"/>',
				'<xsl:value-of select="3"/>'
			],
			[
				'<xsl:value-of select="text()"/>',
				'<xsl:value-of select="text()"/>'
			],
			[
				'<xsl:value-of select="document()"/>',
				'<xsl:value-of select="document()"/>'
			],
			[
				'<xsl:value-of select="foo()"/>',
				'<xsl:value-of select="foo()"/>'
			],
			[
				'<xsl:value-of select="false()"/>',
				'<xsl:value-of select="false()"/>'
			],
			[
				'<xsl:value-of select="1 and 1"/>',
				'<xsl:value-of select="1 and 1"/>'
			],
			[
				'<xsl:value-of select="translate(\'document()\',\'d\',\'D\')"/>',
				'<xsl:value-of select="\'Document()\'"/>'
			],
		];
	}
}