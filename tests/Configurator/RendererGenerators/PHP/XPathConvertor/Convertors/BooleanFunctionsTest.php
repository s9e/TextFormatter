<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanFunctions
*/
class BooleanFunctionsTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// BooleanParam
			[
				'boolean($FOO)',
				"\$this->params['FOO']!==''"
			],
			// HasAttribute
			[
				'boolean(@foo)',
				"\$node->hasAttribute('foo')"
			],
			// HasAttributes
			[
				'boolean(@*)',
				'$node->attributes->length'
			],
			// Not
			[
				"not('a'='a')",
				"!('a'==='a')"
			],
			// NotAttribute
			[
				'not(@foo)',
				"!\$node->hasAttribute('foo')"
			],
			// NotParam
			[
				'not($FOO)',
				"\$this->params['FOO']===''"
			],
		];
	}
}