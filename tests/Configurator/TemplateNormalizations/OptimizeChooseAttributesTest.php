<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChooseAttributes
*/
class OptimizeChooseAttributesTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<xsl:choose>
					<xsl:when test="@foo"/>
					<xsl:otherwise/>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo"/>
					<xsl:otherwise/>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:attribute name="data-foo">FOO</xsl:attribute></xsl:when>
					<xsl:otherwise><xsl:attribute name="data-bar">BAR</xsl:attribute></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:attribute name="data-foo">FOO</xsl:attribute></xsl:when>
					<xsl:otherwise><xsl:attribute name="data-bar">BAR</xsl:attribute></xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:attribute name="data">FOO</xsl:attribute></xsl:when>
					<xsl:otherwise><xsl:attribute name="data">BAR</xsl:attribute></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:attribute name="data">FOO</xsl:attribute></xsl:when>
					<xsl:otherwise><xsl:attribute name="data">BAR</xsl:attribute></xsl:otherwise>
				</xsl:choose>',
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@foo"><xsl:attribute name="data">value</xsl:attribute></xsl:when>
						<xsl:otherwise><xsl:attribute name="data">value</xsl:attribute></xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:attribute name="data">value</xsl:attribute>
					<xsl:choose>
						<xsl:when test="@foo"/>
						<xsl:otherwise/>
					</xsl:choose>'
				),
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@foo">
							<xsl:attribute name="class">foo</xsl:attribute>
							<xsl:attribute name="id">bar</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="class">foo</xsl:attribute>
							<xsl:attribute name="id">bar</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:attribute name="class">foo</xsl:attribute>
					<xsl:attribute name="id">bar</xsl:attribute>
					<xsl:choose>
						<xsl:when test="@foo"/>
						<xsl:otherwise/>
					</xsl:choose>'
				),
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@foo">
							<xsl:attribute name="class">foo</xsl:attribute>
							<xsl:attribute name="id">bar</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="id">bar</xsl:attribute>
							<xsl:attribute name="class">foo</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:attribute name="class">foo</xsl:attribute>
					<xsl:attribute name="id">bar</xsl:attribute>
					<xsl:choose>
						<xsl:when test="@foo"/>
						<xsl:otherwise/>
					</xsl:choose>'
				),
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@foo">
							<xsl:attribute name="class">foo</xsl:attribute>
							<xsl:attribute name="id">bar</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="id">foo</xsl:attribute>
							<xsl:attribute name="class">foo</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>'
				),
				self::ws(
					'<xsl:attribute name="class">foo</xsl:attribute>
					<xsl:choose>
						<xsl:when test="@foo">
							<xsl:attribute name="id">bar</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="id">foo</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>'
				),
			],
		];
	}
}