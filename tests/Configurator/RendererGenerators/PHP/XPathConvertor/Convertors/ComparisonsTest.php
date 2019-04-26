<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Comparisons
*/
class ComparisonsTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// Eq
			[
				"'x'='y'",
				"'x'==='y'"
			],
			[
				'1=1',
				"1==1"
			],
			[
				'@foo!=1',
				"\$node->getAttribute('foo')!=1"
			],
			[
				'0777=777',
				"777==777"
			],
			// Gt
			[
				'@foo > 1',
				"\$node->getAttribute('foo')>1"
			],
			[
				// We cannot reliably evaluate if @foo is NaN
				'@foo > -1',
				false
			],
			// Gte
			[
				'@foo >= 1',
				"\$node->getAttribute('foo')>=1"
			],
			[
				// We cannot reliably evaluate if @foo is NaN
				'@foo >= 0',
				false
			],
			// Lt
			[
				'3 < @foo',
				"3<\$node->getAttribute('foo')"
			],
			[
				// We cannot reliably evaluate if @foo is NaN
				'@foo < 3',
				false
			],
			// Lte
			[
				'3 <= @foo',
				"3<=\$node->getAttribute('foo')"
			],
			[
				// We cannot reliably evaluate if @foo is NaN
				'@foo <= 0',
				false
			],
		];
	}
}