<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveComments
*/
class RemoveCommentsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<!-- This is a comment -->hi<!-- That one too -->',
				'hi'
			],
		];
	}
}