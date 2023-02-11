<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineCDATA
*/
class InlineCDATATest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<![CDATA[<br/>]]><![CDATA[<br/>]]>',
				'&lt;br/&gt;&lt;br/&gt;'
			],
			[
				'<b>.</b><![CDATA[  ]]><b>.</b>',
				'<b>.</b><xsl:text>  </xsl:text><b>.</b>'
			],
		];
	}
}