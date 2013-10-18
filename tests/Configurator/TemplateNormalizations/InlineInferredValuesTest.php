<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMDocument;
use s9e\TextFormatter\Configurator\TemplateNormalizations\InlineInferredValues;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineInferredValues
*/
class InlineInferredValuesTest extends AbstractTest
{
	/**
	* @testdox normalize() ignores unknown tokens from TemplateHelper::parseAttributeValueTemplate()
	* @requires function runkit_method_redefine
	* @runInSeparateProcess
	*/
	public function testUnknownToken()
	{
		class_exists('s9e\\TextFormatter\\Configurator\\Helpers\\TemplateHelper');

		runkit_method_redefine(
			's9e\\TextFormatter\\Configurator\\Helpers\\TemplateHelper',
			'parseAttributeValueTemplate',
			'',
			"return [['foo']];",
			RUNKIT_ACC_STATIC
		);

		$dom = new DOMDocument;
		$dom->loadXML(
			'<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:if test=".=\':)\'"><hr title="{.}"/></xsl:if>
			</xsl:template>'
		);

		$normalizer = new InlineInferredValues;
		$normalizer->normalize($dom->documentElement);
	}

	public function getData()
	{
		return [
			[
				'<xsl:if test=".=\':)\'"><xsl:value-of select="."/></xsl:if>',
				'<xsl:if test=".=\':)\'">:)</xsl:if>'
			],
			[
				'<xsl:choose>
					<xsl:when test=".=\':)\'"><img alt="{.}"/></xsl:when>
					<xsl:when test=".=\':(\'"><img alt="{.}"/></xsl:when>
					<xsl:when test=".=\'x\'or.=\'y\'"><img alt="{.}"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test=".=\':)\'"><img alt=":)"/></xsl:when>
					<xsl:when test=".=\':(\'"><img alt=":("/></xsl:when>
					<xsl:when test=".=\'x\'or.=\'y\'"><img alt="{.}"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>
				</xsl:choose>',
			],
			[
				'<xsl:if test=".=\':)\'"><hr title="{{.}}{@foo}"/></xsl:if>',
				'<xsl:if test=".=\':)\'"><hr title="{{.}}{@foo}"/></xsl:if>',
			],
		];
	}
}