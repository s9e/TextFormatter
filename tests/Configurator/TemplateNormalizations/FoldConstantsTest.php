<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\FoldConstants
*/
class FoldConstantsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<iframe height="{300 + 20}"/>',
				'<iframe height="{320}"/>',
			],
			[
				'<iframe height="{300+20}"/>',
				'<iframe height="{320}"/>',
			],
		];
	}
}