<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\InlineCDATA
*/
class InlineCDATATest extends AbstractTest
{
	public function getData()
	{
		return array(
			array(
				'<![CDATA[<br/>]]><![CDATA[<br/>]]>',
				'&lt;br/&gt;&lt;br/&gt;'
			),
		);
	}
}