<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\SortAttributesByName
*/
class SortAttributesByNameTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<b id="x" class="y"><xsl:apply-templates/></b>',
				'<b class="y" id="x"><xsl:apply-templates/></b>'
			],
		];
	}
}