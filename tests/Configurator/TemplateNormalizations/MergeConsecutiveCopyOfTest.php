<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\MergeConsecutiveCopyOf
*/
class MergeConsecutiveCopyOfTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<hr><xsl:copy-of select="@class"/></hr>',
				'<hr><xsl:copy-of select="@class"/></hr>',
			],
			[
				'<hr><xsl:copy-of select="@class"/><xsl:copy-of select="@style"/></hr>',
				'<hr><xsl:copy-of select="@class|@style"/></hr>',
			],
			[
				'<hr><xsl:copy-of select="@class"/><xsl:copy-of select="@style"/><xsl:copy-of select="@title"/></hr>',
				'<hr><xsl:copy-of select="@class|@style|@title"/></hr>',
			],
			[
				'<div><xsl:copy-of select="@class"/><xsl:copy-of select="@style"/><xsl:copy-of select="@title"/><xsl:apply-templates/></div>',
				'<div><xsl:copy-of select="@class|@style|@title"/><xsl:apply-templates/></div>',
			],
			[
				'<div><xsl:copy-of select="@class"/><xsl:if test="@foo"><xsl:copy-of select="@style"/></xsl:if><xsl:apply-templates/></div>',
				'<div><xsl:copy-of select="@class"/><xsl:if test="@foo"><xsl:copy-of select="@style"/></xsl:if><xsl:apply-templates/></div>'
			],
		];
	}
}