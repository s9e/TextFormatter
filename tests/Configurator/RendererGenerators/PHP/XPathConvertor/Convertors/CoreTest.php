<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Core
*/
class CoreTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// Attribute
			[
				'@foo',
				"\$node->getAttribute('foo')"
			],
			[
				'@  foo',
				"\$node->getAttribute('foo')"
			],
			// Dot
			[
				'.',
				'$node->textContent'
			],
			// LiteralNumber
			[
				'123',
				'123'
			],
			[
				'0777',
				'777'
			],
			[
				'-123',
				'-123'
			],
			[
				'-0777',
				'-777'
			],
			// LiteralString
			[
				"'foo'",
				"'foo'"
			],
			[
				'"foo"',
				"'foo'"
			],
			// LocalName
			[
				'local-name()',
				'$node->localName'
			],
			[
				'local-name ()',
				'$node->localName'
			],
			// Name
			[
				'name()',
				'$node->nodeName'
			],
			[
				'name ()',
				'$node->nodeName'
			],
			// Parameter
			[
				'$FOO',
				"\$this->params['FOO']"
			],
		];
	}
}