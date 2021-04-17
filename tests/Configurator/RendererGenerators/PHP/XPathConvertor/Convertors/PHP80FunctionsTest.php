<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\PHP80Functions;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\PHP80Functions
*/
class PHP80FunctionsTest extends AbstractConvertorTest
{
	protected function getMatchers($parser)
	{
		$matchers   = parent::getMatchers($parser);
		$matchers[] = new PHP80Functions($parser);

		return $matchers;
	}

	public function getConvertorTests()
	{
		return [
			// Contains
			[
				'contains(@foo, @bar)',
				"str_contains(\$node->getAttribute('foo'),\$node->getAttribute('bar'))"
			],
			// EndsWith
			[
				'ends-with(@foo, @bar)',
				"str_ends_with(\$node->getAttribute('foo'),\$node->getAttribute('bar'))"
			],
			[
				'ends-with(@foo, "/")',
				"str_ends_with(\$node->getAttribute('foo'),'/')"
			],
			// NotContains
			[
				'not(contains(@foo, @bar))',
				"!str_contains(\$node->getAttribute('foo'),\$node->getAttribute('bar'))"
			],
			// NotEndsWith
			[
				"not(ends-with(@foo, '//'))",
				"!str_ends_with(\$node->getAttribute('foo'),'//')"
			],
			// NotStartsWith
			[
				'not(starts-with(@foo, @bar))',
				"!str_starts_with(\$node->getAttribute('foo'),\$node->getAttribute('bar'))"
			],
			// StartsWith
			[
				'starts-with(@foo, @bar)',
				"str_starts_with(\$node->getAttribute('foo'),\$node->getAttribute('bar'))"
			],
		];
	}
}