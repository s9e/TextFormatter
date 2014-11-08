<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\MinifyXPathExpressions
*/
class MinifyXPathExpressionsTest extends AbstractTest
{
	/**
	* @testdox Throws an exception if a string isn't properly closed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot parse XPath expression 'foo = "bar'
	*/
	public function testInvalidXPath()
	{
		$this->test('<xsl:if test="foo = &quot;bar">!</xsl:if>', null);
	}

	public function getData()
	{
		return array(
			array(
				'<div><xsl:value-of select=" @foo "/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			),
			array(
				'<div><xsl:value-of select="@ foo"/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			),
			array(
				'<div><xsl:value-of select="concat(@foo, @bar, @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,@bar,@baz)"/></div>'
			),
			array(
				'<div><xsl:value-of select="concat(@foo, \' @bar \', @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,\' @bar \',@baz)"/></div>'
			),
			array(
				'<div><xsl:if test="@foo = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo=2">!</xsl:if></div>'
			),
			array(
				'<div><xsl:value-of select="substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))"/></div>',
				'<div><xsl:value-of select="substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))"/></div>'
			),
			array(
				'<div><xsl:if test="@foo - bar = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo -bar=2">!</xsl:if></div>'
			),
			array(
				'<div><xsl:if test="@foo- - 1 = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo- -1=2">!</xsl:if></div>'
			),
			array(
				'<div><xsl:if test=" foo or _bar ">!</xsl:if></div>',
				'<div><xsl:if test="foo or _bar">!</xsl:if></div>'
			),
			array(
				'<b title="foo { @ bar } baz { @ quux }"/>',
				'<b title="foo {@bar} baz {@quux}"/>'
			),
			array(
				'<b title="{{foo}} { @bar } {{{ @baz }}}"/>',
				'<b title="{{foo}} {@bar} {{{@baz}}}"/>'
			),
			array(
				'<b title="{ &quot;&amp;lt;&quot; }"/>',
				'<b title="{&quot;&amp;lt;&quot;}"/>'
			),
		);
	}
}