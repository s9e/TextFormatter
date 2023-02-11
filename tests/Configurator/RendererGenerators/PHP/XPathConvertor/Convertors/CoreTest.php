<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Core
*/
class CoreTest extends AbstractConvertorTestClass
{
	public static function getConvertorTests()
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
			[
				'@foo-1',
				"\$node->getAttribute('foo-1')"
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