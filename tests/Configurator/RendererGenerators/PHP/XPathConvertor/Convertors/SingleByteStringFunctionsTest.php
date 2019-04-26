<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringFunctions
*/
class SingleByteStringFunctionsTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// Contains
			[
				'contains(@foo, @bar)',
				"(strpos(\$node->getAttribute('foo'),\$node->getAttribute('bar'))!==false)"
			],
			// EndsWith
			[
				'ends-with(@foo, @bar)',
				"preg_match('('.preg_quote(\$node->getAttribute('bar')).'\$)D',\$node->getAttribute('foo'))"
			],
			[
				'ends-with(@foo, "/")',
				"(substr(\$node->getAttribute('foo'),-1)==='/')"
			],
			[
				"ends-with(@foo, '//')",
				"(substr(\$node->getAttribute('foo'),-2)==='//')"
			],
			[
				"ends-with(@foo, '😊')",
				"(substr(\$node->getAttribute('foo'),-4)==='😊')"
			],
			// NotContains
			[
				'not(contains(@foo, @bar))',
				"(strpos(\$node->getAttribute('foo'),\$node->getAttribute('bar'))===false)"
			],
			// NotEndsWith
			[
				"not(ends-with(@foo, '//'))",
				"(substr(\$node->getAttribute('foo'),-2)!=='//')"
			],
			// NotStartsWith
			[
				'not(starts-with(@foo, @bar))',
				"(strpos(\$node->getAttribute('foo'),\$node->getAttribute('bar'))!==0)"
			],
			// StartsWith
			[
				'starts-with(@foo, @bar)',
				"(strpos(\$node->getAttribute('foo'),\$node->getAttribute('bar'))===0)"
			],
			// StringLength
			[
				'string-length(@foo)',
				"preg_match_all('(.)su',\$node->getAttribute('foo'))"
			],
		];
	}
}