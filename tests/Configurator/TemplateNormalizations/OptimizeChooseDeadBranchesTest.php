<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChooseDeadBranches
*/
class OptimizeChooseDeadBranchesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:when test="false()">false</xsl:when>
					<xsl:otherwise>true</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					
					<xsl:otherwise>true</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="false()">false</xsl:when>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise>true</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise>true</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:when test="true()">true</xsl:when>
					<xsl:when test="@bar">bar</xsl:when>
					<xsl:when test="@baz">baz</xsl:when>
					<xsl:otherwise>never</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise>true</xsl:otherwise>
					
					
					
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:when test="@foo">never</xsl:when>
					<xsl:otherwise>else</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					
					<xsl:otherwise>else</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="false()">false</xsl:when>
					<xsl:when test="0">false</xsl:when>
					<xsl:when test="0.0">false</xsl:when>
					<xsl:when test="\'\'">false</xsl:when>
					<xsl:when test=\'""\'>false</xsl:when>
					<xsl:when test="00">false</xsl:when>
					<xsl:otherwise>always</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					
					
					
					
					
					
					<xsl:otherwise>always</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="01">always</xsl:when>
					<xsl:otherwise>never</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:otherwise>always</xsl:otherwise>
					
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test=".01">always</xsl:when>
					<xsl:otherwise>never</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:otherwise>always</xsl:otherwise>
					
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="\'0\'">always</xsl:when>
					<xsl:otherwise>never</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:otherwise>always</xsl:otherwise>
					
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="1.0">always</xsl:when>
					<xsl:otherwise>never</xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:otherwise>always</xsl:otherwise>
					
				</xsl:choose>'
			],
		];
	}
}