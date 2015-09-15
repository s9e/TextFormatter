<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\FoldArithmeticConstants
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractConstantFolding
*/
class FoldArithmeticConstantsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<iframe height="{300 + 20}"/>',
				'<iframe height="{320}"/>',
			],
			[
				'<iframe><xsl:attribute name="height"><xsl:value-of select="300 + 20"/></xsl:attribute></iframe>',
				'<iframe><xsl:attribute name="height"><xsl:value-of select="320"/></xsl:attribute></iframe>',
			],
			[
				'<iframe height="{300+20}"/>',
				'<iframe height="{320}"/>',
			],
			[
				'<iframe height="{100 * 2}"/>',
				'<iframe height="{200}"/>',
			],
			[
				'<iframe height="{100 * 2 * 7}"/>',
				'<iframe height="{1400}"/>',
			],
			[
				'<iframe height="{100 * 2 + 7}"/>',
				'<iframe height="{207}"/>',
			],
			[
				'<iframe height="{100 + 2 * 7}"/>',
				'<iframe height="{114}"/>',
			],
			[
				'<iframe height="{100*315div560}"/>',
				'<iframe height="{56.25}"/>',
			],
			[
				'<iframe height="{100 * (315 + 7) div 560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<iframe height="{100 * (315 + 4 + 3) div 560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<iframe height="{100*(315+4+3)div560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<xsl:value-of select="(1 + 2) * (3 + 4)"/>',
				'<xsl:value-of select="21"/>'
			],
			[
				'<xsl:value-of select="((1 + 2) * 3) + 4"/>',
				'<xsl:value-of select="13"/>'
			],
			[
				'<xsl:value-of select="1 + (2 * 3) + 4"/>',
				'<xsl:value-of select="11"/>'
			],
			[
				'<xsl:value-of select="@foo + 0"/>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:value-of select="0 + @foo"/>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:value-of select="@foo + 0 + @bar"/>',
				'<xsl:value-of select="@foo + @bar"/>'
			],
			[
				'<xsl:value-of select="@foo + 0 * @bar"/>',
				'<xsl:value-of select="@foo + 0 * @bar"/>'
			],
			[
				'<xsl:value-of select="(@foo + 0) * @bar"/>',
				'<xsl:value-of select="(@foo) * @bar"/>'
			],
		];
	}
}