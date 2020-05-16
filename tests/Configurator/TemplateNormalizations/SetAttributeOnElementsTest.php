<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\SetAttributeOnElements
*/
class SetAttributeOnElementsTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<a>...</a>',
				'<a rel="ugc">...</a>',
				['//a', 'rel', 'ugc']
			],
			[
				'<a>...</a>',
				'<a>...</a>',
				['//a[@target]', 'rel', 'noreferrer']
			],
			[
				'<a target="_blank">...</a>',
				'<a target="_blank" rel="noreferrer">...</a>',
				['//a[@target]', 'rel', 'noreferrer']
			],
			[
				'<a target="_blank" rel="noreferrer">...</a>',
				'<a target="_blank" rel="noreferrer">...</a>',
				['//a[@target]', 'rel', 'noreferrer']
			],
			[
				'<a target="_blank" rel="noreferrerer">...</a>',
				'<a target="_blank" rel="noreferrer">...</a>',
				['//a[@target]', 'rel', 'noreferrer']
			],
		];
	}
}