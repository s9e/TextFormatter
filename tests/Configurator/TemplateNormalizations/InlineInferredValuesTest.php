<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMDocument;
use s9e\TextFormatter\Configurator\TemplateNormalizations\InlineInferredValues;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineInferredValues
*/
class InlineInferredValuesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<xsl:if test=".=\':)\'"><xsl:value-of select="."/></xsl:if>',
				'<xsl:if test=".=\':)\'">:)</xsl:if>'
			],
			[
				'<xsl:if test=".=\'x\'or.=\'y\'"><xsl:value-of select="."/></xsl:if>',
				'<xsl:if test=".=\'x\'or.=\'y\'"><xsl:value-of select="."/></xsl:if>'
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
			[
				'<xsl:if test="@foo=\'foo\'"><hr title="{@foo} &amp; {@bar}"/></xsl:if>',
				'<xsl:if test="@foo=\'foo\'"><hr title="foo &amp; {@bar}"/></xsl:if>',
			],
		];
	}
}