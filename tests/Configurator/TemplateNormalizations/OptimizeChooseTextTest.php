<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChooseText
*/
class OptimizeChooseTextTest extends AbstractTestClass
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
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:when test="@bar">foo</xsl:when>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:when test="@bar">foo</xsl:when>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">food</xsl:when>
					<xsl:when test="@bar">fool</xsl:when>
					<xsl:otherwise>foot</xsl:otherwise>
				</xsl:choose>',
				'foo<xsl:choose>
					<xsl:when test="@foo">d</xsl:when>
					<xsl:when test="@bar">l</xsl:when>
					<xsl:otherwise>t</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">loot</xsl:when>
					<xsl:when test="@bar">moot</xsl:when>
					<xsl:otherwise>root</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">l</xsl:when>
					<xsl:when test="@bar">m</xsl:when>
					<xsl:otherwise>r</xsl:otherwise>
				</xsl:choose>oot'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">12345<b>..</b>4321</xsl:when>
					<xsl:otherwise>1xx<i>..</i>x321</xsl:otherwise>
				</xsl:choose>',
				'1<xsl:choose>
					<xsl:when test="@foo">2345<b>..</b>4</xsl:when>
					<xsl:otherwise>xx<i>..</i>x</xsl:otherwise>
				</xsl:choose>321'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo"><b>..</b>4321</xsl:when>
					<xsl:otherwise>1xx<i>..</i>x321</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo"><b>..</b>4</xsl:when>
					<xsl:otherwise>1xx<i>..</i>x</xsl:otherwise>
				</xsl:choose>321'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">12345<b>..</b></xsl:when>
					<xsl:otherwise>1xx<i>..</i>x321</xsl:otherwise>
				</xsl:choose>',
				'1<xsl:choose>
					<xsl:when test="@foo">2345<b>..</b></xsl:when>
					<xsl:otherwise>xx<i>..</i>x321</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">xxx<b>...</b>yz</xsl:when>
					<xsl:otherwise>xxxyz</xsl:otherwise>
				</xsl:choose>',
				'xxx<xsl:choose>
					<xsl:when test="@foo"><b>...</b></xsl:when>
					<xsl:otherwise></xsl:otherwise>
				</xsl:choose>yz'
			],
		];
	}
}