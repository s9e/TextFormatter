<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\MinifyXPathExpressions
*/
class MinifyXPathExpressionsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div><xsl:value-of select=" @foo "/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			],
			[
				'<div><xsl:value-of select="@ foo"/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			],
			[
				'<div><xsl:value-of select="concat(@foo, @bar, @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,@bar,@baz)"/></div>'
			],
			[
				'<div><xsl:value-of select="concat(@foo, \' @bar \', @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,\' @bar \',@baz)"/></div>'
			],
			[
				'<div><xsl:if test="@foo = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo=2">!</xsl:if></div>'
			],
			[
				'<div><xsl:value-of select="substring(., 1 + string-length(s), string-length() - (string-length(s) + string-length(e)))"/></div>',
				'<div><xsl:value-of select="substring(.,1+string-length(s),string-length()-(string-length(s)+string-length(e)))"/></div>'
			],
			[
				'<div><xsl:if test="@foo - bar = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo -bar=2">!</xsl:if></div>'
			],
			[
				'<div><xsl:if test="@foo- - 1 = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo- -1=2">!</xsl:if></div>'
			],
			[
				'<div><xsl:if test=" foo or _bar ">!</xsl:if></div>',
				'<div><xsl:if test="foo or_bar">!</xsl:if></div>'
			],
			[
				'<b title="foo { @ bar } baz { @ quux }"/>',
				'<b title="foo {@bar} baz {@quux}"/>'
			],
			[
				'<b title="{{foo}} { @bar } {{{ @baz }}}"/>',
				'<b title="{{foo}} {@bar} {{{@baz}}}"/>'
			],
			[
				'<b title="{ &quot;&amp;lt;&quot; }"/>',
				'<b title="{&quot;&amp;lt;&quot;}"/>'
			],
			[
				'<b title="{(1+1)}"/>',
				'<b title="{1+1}"/>'
			],
		];
	}
}