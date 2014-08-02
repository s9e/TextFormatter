<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\XSLT;

use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer
*/
class OptimizerTest extends Test
{
	/**
	* @dataProvider getOptimizeTemplateTests
	* @testdox optimizeTemplate() tests
	*/
	public function testOptimizations($original, $expected)
	{
		$optimizer = new Optimizer;

		$this->assertSame($expected, $optimizer->optimizeTemplate($original));
	}

	public function getOptimizeTemplateTests()
	{
		return [
			[
				'<xsl:choose><xsl:when test="@a=1">a1</xsl:when><xsl:otherwise><xsl:choose><xsl:when test="@b=1">b1</xsl:when><xsl:otherwise>X</xsl:otherwise></xsl:choose></xsl:otherwise></xsl:choose>',
				'<xsl:choose><xsl:when test="@a=1">a1</xsl:when><xsl:when test="@b=1">b1</xsl:when><xsl:otherwise>X</xsl:otherwise></xsl:choose>'
			],
		];
	}
}