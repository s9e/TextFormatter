<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeConditionalValueOf
*/
class OptimizeConditionalValueOfTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<xsl:if test="@foo"><xsl:value-of select="@foo"/></xsl:if>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:if test="@data-foo"><xsl:value-of select="@data-foo"/></xsl:if>',
				'<xsl:value-of select="@data-foo"/>'
			],
			[
				'<div><xsl:attribute name="title"><xsl:if test="@foo"><xsl:value-of select="@foo"/></xsl:if></xsl:attribute></div>',
				'<div><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></div>'
			],
			[
				'<xsl:if test="@foo"><xsl:value-of select="@bar"/></xsl:if>',
				'<xsl:if test="@foo"><xsl:value-of select="@bar"/></xsl:if>'
			],
			[
				'<xsl:if test="1+@foo"><xsl:value-of select="1+@foo"/></xsl:if>',
				'<xsl:if test="1+@foo"><xsl:value-of select="1+@foo"/></xsl:if>'
			],
		];
	}
}