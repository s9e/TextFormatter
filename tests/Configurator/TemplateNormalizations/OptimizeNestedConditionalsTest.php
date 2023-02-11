<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeNestedConditionals
*/
class OptimizeNestedConditionalsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:when test="@b=1">b1</xsl:when>
						<xsl:otherwise>X</xsl:otherwise>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:otherwise>
							<hr/>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:otherwise>
							<hr/>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">a1</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
							<xsl:choose>
								<xsl:when test="@b=1">b1</xsl:when>
								<xsl:otherwise>X</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>'
				)
			],
		];
	}
}