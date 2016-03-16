<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\SetRelNoreferrerOnTargetedLinks
*/
class SetRelNoreferrerOnTargetedLinksTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<a>...</a>',
				'<a>...</a>'
			],
			[
				'<a target="_blank">...</a>',
				'<a target="_blank" rel="noreferrer">...</a>'
			],
			[
				'<a target="_blank" rel="noreferrer">...</a>',
				'<a target="_blank" rel="noreferrer">...</a>'
			],
			[
				'<a target="_blank" rel="noopener">...</a>',
				'<a target="_blank" rel="noopener">...</a>'
			],
			[
				'<a target="_blank" rel="noreferrerer">...</a>',
				'<a target="_blank" rel="noreferrerer noreferrer">...</a>'
			],
			[
				'<a target="foo">...</a>',
				'<a target="foo" rel="noreferrer">...</a>'
			],
			[
				'<area target="_blank">...</area>',
				'<area target="_blank" rel="noreferrer">...</area>'
			],
		];
	}
}