<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMException;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineAttributes
*/
class InlineAttributesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				// <xsl:attribute/> with one single <xsl:value-of/> child is inlined
				'<div><xsl:attribute name="class"><xsl:value-of select="@foo"/></xsl:attribute><xsl:apply-templates/></div>',
				'<div class="{@foo}"><xsl:apply-templates/></div>'
			],
			[
				// <xsl:attribute/> with an invalid name results in an exception
				'<div><xsl:attribute name="foo#bar">x</xsl:attribute><xsl:apply-templates/></div>',
				new DOMException
			],
			[
				// <xsl:attribute/> with <xsl:value-of/>, <xsl:text/> and text nodes descendants is inlined
				'<div><xsl:attribute name="class">foo <xsl:value-of select="@bar"/><xsl:text> baz</xsl:text></xsl:attribute><xsl:apply-templates/></div>',
				'<div class="foo {@bar} baz"><xsl:apply-templates/></div>'
			],
			[
				// <xsl:attribute/> child of namespaced element is inlined
				'<svg xmlns="http://www.w3.org/2000/svg"><xsl:attribute name="id">foo</xsl:attribute></svg>',
				'<svg xmlns="http://www.w3.org/2000/svg" id="foo"/>'
			],
			[
				// Curly brackets in text are escaped when attributes are inlined
				'<div><xsl:attribute name="title">{foo}</xsl:attribute><xsl:apply-templates/></div>',
				'<div title="{{foo}}"><xsl:apply-templates/></div>'
			],
			[
				// <xsl:attribute/> with <xsl:if/> child is not inlined
				'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<hr><xsl:attribute name="title">&amp;&lt;&gt;"</xsl:attribute></hr>',
				'<hr title="&amp;&lt;&gt;&quot;"/>',
			],
		];
	}
}