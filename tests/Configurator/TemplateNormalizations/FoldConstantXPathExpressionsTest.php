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
				'<xsl:value-of select="2"/>'
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
				'<xsl:value-of select="3"/>'
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
				'<xsl:value-of select="5 div 0"/>',
				'<xsl:value-of select="5 div 0"/>'
			],
			[
				"<xsl:value-of select=\"translate('document()','d','D')\"/>",
				"<xsl:value-of select=\"'Document()'\"/>"
			],
			[
				"<xsl:value-of select=\"translate('concat()','c','C')\"/>",
				"<xsl:value-of select=\"'ConCat()'\"/>"
			],
			[
				"<xsl:value-of select=\"substring-after('foobar', 'foo')\"/>",
				"<xsl:value-of select=\"'bar'\"/>"
			],
			[
				'<xsl:value-of select="string-length(\'abc\')"/>',
				'<xsl:value-of select="3"/>'
			],
			[
				'<xsl:value-of select="string-length(@foo)"/>',
				'<xsl:value-of select="string-length(@foo)"/>'
			],
			[
				'<xsl:value-of select="string-length()"/>',
				'<xsl:value-of select="string-length()"/>'
			],
			[
				'<xsl:value-of select="normalize-space(\'  a  b  c  \')"/>',
				'<xsl:value-of select="\'a b c\'"/>'
			],
			[
				'<xsl:value-of select="normalize-space(@foo)"/>',
				'<xsl:value-of select="normalize-space(@foo)"/>'
			],
			[
				'<xsl:value-of select="normalize-space()"/>',
				'<xsl:value-of select="normalize-space()"/>'
			],
			[
				"<b title=\"{concat('}','}}')}\"><xsl:apply-templates/></b>",
				'<b title="{\'}}}\'}"><xsl:apply-templates/></b>',
			],
			[
				'<xsl:value-of select="contains(\'foobar\', \'foo\')"/>',
				'<xsl:value-of select="true()"/>',
			],
			[
				'<xsl:value-of select="contains(\'foo\', \'foobar\')"/>',
				'<xsl:value-of select="false()"/>',
			],
			[
				'<xsl:if test="starts-with(\'foobar\', \'foo\')">..</xsl:if>',
				'<xsl:if test="true()">..</xsl:if>'
			],
			[
				'<xsl:if test="not(starts-with(\'foobar\', \'foo\'))">..</xsl:if>',
				'<xsl:if test="false()">..</xsl:if>'
			],
			[
				'<xsl:if test="1 &gt; 2">..</xsl:if>',
				'<xsl:if test="false()">..</xsl:if>'
			],
			[
				'<xsl:if test="1.1 = 1.1">..</xsl:if>',
				'<xsl:if test="true()">..</xsl:if>'
			],
			[
				'<xsl:if test="1.2 != 1.1">..</xsl:if>',
				'<xsl:if test="true()">..</xsl:if>'
			],
			[
				'<xsl:if test="1.10 != 1.1">..</xsl:if>',
				'<xsl:if test="false()">..</xsl:if>'
			],
			[
				'<xsl:if test="\'foobar\' = \'foobar\'">..</xsl:if>',
				'<xsl:if test="true()">..</xsl:if>'
			],
		];
	}
}