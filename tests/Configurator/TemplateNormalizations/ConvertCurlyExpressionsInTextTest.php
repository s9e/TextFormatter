<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\ConvertCurlyExpressionsInText
*/
class ConvertCurlyExpressionsInTextTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<span>{$FOO}{@bar}</span>',
				'<span><xsl:value-of select="$FOO"/><xsl:value-of select="@bar"/></span>'
			],
			[
				'0<span>1{$FOO}2{@bar}3</span>4',
				'0<span>1<xsl:value-of select="$FOO"/>2<xsl:value-of select="@bar"/>3</span>4'
			],
			[
				// Text inside of XSL elements is ignored
				'<span><xsl:text>{$FOO}{@bar}</xsl:text></span>',
				'<span><xsl:text>{$FOO}{@bar}</xsl:text></span>'
			],
			[
				// Only single variables and attributes are accepted
				'<script>if (foo) { alert($BAR); }</script>',
				'<script>if (foo) { alert($BAR); }</script>'
			],
			[
				'<span>{@foo}  {@bar}</span>',
				'<span><xsl:value-of select="@foo"/><xsl:text>  </xsl:text><xsl:value-of select="@bar"/></span>',
			],
		];
	}
}