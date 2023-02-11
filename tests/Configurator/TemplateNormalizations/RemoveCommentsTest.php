<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveComments
*/
class RemoveCommentsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<!-- This is a comment -->hi<!-- That one too -->',
				'hi'
			],
		];
	}
}