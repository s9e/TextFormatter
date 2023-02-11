<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\MergeIdenticalConditionalBranches
*/
class MergeIdenticalConditionalBranchesTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=2">even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
						<xsl:when test="@a=4">even</xsl:when>
						<xsl:when test="@a=5">odd</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1 or @a=3 or @a=5">odd</xsl:when>
						<xsl:when test="@a=2 or @a=4">even</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=2">even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
						<xsl:when test="@b=4">B-even</xsl:when>
						<xsl:when test="@a=5">odd</xsl:when>
						<xsl:when test="@a=6">even</xsl:when>
						<xsl:when test="@a=7">odd</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1 or @a=3">odd</xsl:when>
						<xsl:when test="@a=2">even</xsl:when>
						<xsl:when test="@b=4">B-even</xsl:when>
						<xsl:when test="@a=5 or @a=7">odd</xsl:when>
						<xsl:when test="@a=6">even</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1 or @a=3">odd</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=3">not even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=3">not even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">...</xsl:when>
						<xsl:when test="@a=2">even</xsl:when>
						<xsl:when test="@a!=0">...</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">...</xsl:when>
						<xsl:when test="@a=2">even</xsl:when>
						<xsl:when test="@a!=0">...</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=2 or @b=2">even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@a=1">odd</xsl:when>
						<xsl:when test="@a=2 or @b=2">even</xsl:when>
						<xsl:when test="@a=3">odd</xsl:when>
					</xsl:choose>'
				)
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@x = @y">...</xsl:when>
						<xsl:when test="@z = @x">...</xsl:when>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:choose>
						<xsl:when test="@x = @y or @z = @x">...</xsl:when>
					</xsl:choose>'
				)
			],
		];
	}
}