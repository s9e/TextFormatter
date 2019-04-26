<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringManipulation
*/
class SingleByteStringManipulationTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// Concat
			[
				"concat('foo')",
				"'foo'"
			],
			[
				"concat('foo', 'bar')",
				"'foo'.'bar'"
			],
			[
				"concat(@foo, @bar)",
				"\$node->getAttribute('foo').\$node->getAttribute('bar')"
			],
			[
				"concat( 'foo' , 'bar' , 'baz' )",
				"'foo'.'bar'.'baz'"
			],
			// NormalizeSpace
			[
				"normalize-space(@foo)",
				"preg_replace('(\\\\s+)',' ',trim(\$node->getAttribute('foo')))"
			],
			// SubstringAfter
			[
				"substring-after(@foo, 'bar')",
				"substr(strstr(\$node->getAttribute('foo'),'bar'),3)"
			],
			// SubstringBefore
			[
				"substring-before(@foo, 'bar')",
				"strstr(\$node->getAttribute('foo'),'bar',true)"
			],
			[
				"substring-before(@foo, @bar)",
				"strstr(\$node->getAttribute('foo'),\$node->getAttribute('bar'),true)"
			],
			// Translate
			[
				"translate(@foo, 'abc', 'ABC')",
				"strtr(\$node->getAttribute('foo'),'abc','ABC')"
			],
			[
				"translate(@foo, 'äbc', 'ÄBC')",
				"strtr(\$node->getAttribute('foo'),['ä'=>'Ä','b'=>'B','c'=>'C'])"
			],
		];
	}
}