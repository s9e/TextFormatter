<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeConditionalValueOf
*/
class OptimizeConditionalValueOfTest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<xsl:if test="@foo"><xsl:value-of select="@foo"/></xsl:if>',
				'<xsl:value-of select="@foo"/>'
			),
			array(
				'<xsl:if test="@data-foo"><xsl:value-of select="@data-foo"/></xsl:if>',
				'<xsl:value-of select="@data-foo"/>'
			),
			array(
				'<div><xsl:attribute name="title"><xsl:if test="@foo"><xsl:value-of select="@foo"/></xsl:if></xsl:attribute></div>',
				'<div><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></div>'
			),
			array(
				'<xsl:if test="@foo"><xsl:value-of select="@bar"/></xsl:if>',
				'<xsl:if test="@foo"><xsl:value-of select="@bar"/></xsl:if>'
			),
			array(
				'<xsl:if test="1+@foo"><xsl:value-of select="1+@foo"/></xsl:if>',
				'<xsl:if test="1+@foo"><xsl:value-of select="1+@foo"/></xsl:if>'
			),
		);
	}
}