<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanOperators;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Comparisons;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Core;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Math;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\MultiByteStringManipulation;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringManipulation;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
*/
abstract class AbstractConvertorTestClass extends Test
{
	/**
	* @dataProvider getConvertorTests
	*/
	public function test($original, $expected)
	{
		if ($expected === false)
		{
			$this->expectException('RuntimeException', 'Cannot convert');
		}

		$parser = new RecursiveParser;
		$parser->setMatchers($this->getMatchers($parser));

		$this->assertEquals($expected, $parser->parse($original)['value']);
	}

	protected function getMatchers($parser)
	{
		$matchers   = [];
		$matchers[] = new SingleByteStringFunctions($parser);
		$matchers[] = new BooleanFunctions($parser);
		$matchers[] = new BooleanOperators($parser);
		$matchers[] = new Comparisons($parser);
		$matchers[] = new Core($parser);
		$matchers[] = new Math($parser);
		$matchers[] = new MultiByteStringManipulation($parser);
		$matchers[] = new SingleByteStringManipulation($parser);

		return $matchers;
	}

	abstract public static function getConvertorTests();
}