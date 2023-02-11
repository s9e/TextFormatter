<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineElements
*/
class InlineElementsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				//<xsl:element/> is inlined
				'<xsl:element name="div"><xsl:apply-templates/></xsl:element>',
				'<div><xsl:apply-templates/></div>'
			],
			[
				//<xsl:element namespace="..."/> is inlined
				'<xsl:element name="svg" namespace="http://www.w3.org/2000/svg"/>',
				'<svg xmlns="http://www.w3.org/2000/svg"/>'
			],
			[
				//<xsl:element namespace="..."/> preserves the element\'s prefix
				'<xsl:element name="svg:svg" namespace="http://www.w3.org/2000/svg"/>',
				'<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>'
			],
			[
				//<xsl:element/> with an invalid name is ignored
				'<xsl:element name="foo#bar"/>',
				'<xsl:element name="foo#bar"/>'
			],
		];
	}
}