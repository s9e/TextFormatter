<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\DeoptimizeIf
*/
class DeoptimizeIfTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<xsl:if test="@foo">.<b>..</b>.</xsl:if>',
				'<xsl:choose><xsl:when test="@foo">.<b>..</b>.</xsl:when></xsl:choose>'
			],
		];
	}
}