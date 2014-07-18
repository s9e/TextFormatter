<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer
*/
class SerializerTest extends Test
{
	/**
	* @dataProvider getConvertXPathTests
	* @testdox convertXPath() tests
	*/
	public function testConvertXPath($original, $expected, $setup = null)
	{
		$serializer = new Serializer;

		if (isset($setup))
		{
			$setup($serializer);
		}

		$this->assertSame($expected, $serializer->convertXPath($original));
	}

	/**
	* @dataProvider getConvertConditionTests
	* @testdox convertCondition() tests
	*/
	public function testConvertCondition($original, $expected, $setup = null)
	{
		$serializer = new Serializer;

		if (isset($setup))
		{
			$setup($serializer);
		}

		$this->assertSame($expected, $serializer->convertCondition($original));
	}

	public function getConvertXPathTests()
	{
		return [
			[
				'@bar',
				"\$node->getAttribute('bar')"
			],
			[
				'.',
				"\$node->textContent"
			],
			[
				'$foo',
				"\$this->params['foo']"
			],
			[
				"'foo'",
				"'foo'"
			],
			[
				'"foo"',
				"'foo'"
			],
			[
				'local-name()',
				'$node->localName'
			],
			[
				'name()',
				'$node->nodeName'
			],
			[
				'123',
				"'123'"
			],
			[
				'normalize-space(@bar)',
				"\$this->xpath->evaluate('normalize-space(@bar)',\$node)"
			],
			[
				'string-length(@bar)',
				"\$this->xpath->evaluate('string-length(@bar)',\$node)"
			],
			[
				'string-length(@bar)',
				"mb_strlen(\$node->getAttribute('bar'),'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'string-length()',
				"\$this->xpath->evaluate('string-length()',\$node)"
			],
			[
				'string-length()',
				"mb_strlen(\$node->textContent,'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'substring(.,1,2)',
				"\$this->xpath->evaluate('substring(.,1,2)',\$node)"
			],
			[
				'substring(.,1,2)',
				"mb_substr(\$node->textContent,0,2,'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'substring(.,0,2)',
				"\$this->xpath->evaluate('substring(.,0,2)',\$node)"
			],
			[
				// NOTE: as per XPath specs, the length is adjusted to the negative position
				'substring(.,0,2)',
				"mb_substr(\$node->textContent,0,1,'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'substring(.,@x,1)',
				"\$this->xpath->evaluate('substring(.,@x,1)',\$node)"
			],
			[
				'substring(.,@x,1)',
				"mb_substr(\$node->textContent,max(0,\$node->getAttribute('x')-1),1,'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'substring(.,1,@x)',
				"\$this->xpath->evaluate('substring(.,1,@x)',\$node)"
			],
			[
				'substring(.,1,@x)',
				"mb_substr(\$node->textContent,0,max(0,\$node->getAttribute('x')),'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'substring(.,2)',
				"\$this->xpath->evaluate('substring(.,2)',\$node)"
			],
			[
				'substring(.,2)',
				"mb_substr(\$node->textContent,1,null,'utf-8')",
				function ($serializer)
				{
					$serializer->useMultibyteStringFunctions = true;
				}
			],
			[
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			],
			[
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			],
			[
				'translate(@bar,"éè","ÉÈ")',
				"strtr(\$node->getAttribute('bar'),['é'=>'É','è'=>'È'])"
			],
			[
				'translate(@bar,"ab","ABC")',
				"strtr(\$node->getAttribute('bar'),'ab','AB')"
			],
			[
				'translate(@bar,"abcd","AB")',
				"strtr(\$node->getAttribute('bar'),['a'=>'A','b'=>'B','c'=>'','d'=>''])"
			],
			[
				'translate(@bar,"abbd","ABCD")',
				"strtr(\$node->getAttribute('bar'),'abd','ABD')"
			],
			// Custom representations
			[
				"substring('songWw',6-5*boolean(@songid),5)",
				"(\$node->hasAttribute('songid')?'songW':'w')"
			],
			[
				'400-360*boolean(@songid)',
				"(\$node->hasAttribute('songid')?40:400)"
			],
			// Math
			[
				'@foo + 12',
				"(\$node->getAttribute('foo')+12)",
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
			[
				'44 + $bar',
				"(44+\$this->params['bar'])",
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
		];
	}

	public function getConvertConditionTests()
	{
		return [
			[
				'@foo',
				"\$node->hasAttribute('foo')"
			],
			[
				'not(@foo)',
				"!\$node->hasAttribute('foo')"
			],
			[
				'$foo',
				"!empty(\$this->params['foo'])"
			],
			[
				'not($foo)',
				"empty(\$this->params['foo'])"
			],
			[
				".='foo'",
				"\$node->textContent==='foo'"
			],
			[
				"@foo='foo'",
				"\$node->getAttribute('foo')==='foo'"
			],
			[
				".='fo\"o'",
				"\$node->textContent==='fo\"o'"
			],
			[
				'.=\'"_"\'',
				'$node->textContent===\'"_"\''
			],
			[
				".='foo'or.='bar'",
				"\$node->textContent==='foo'||\$node->textContent==='bar'"
			],
			[
				'.=3',
				"\$node->textContent==3"
			],
			[
				'.=022',
				"\$node->textContent==22"
			],
			[
				'044=.',
				"44==\$node->textContent"
			],
			[
				'@foo != @bar',
				"\$node->getAttribute('foo')!==\$node->getAttribute('bar')"
			],
			[
				'@foo = @bar or @baz',
				"\$node->getAttribute('foo')===\$node->getAttribute('bar')||\$node->hasAttribute('baz')"
			],
			[
				'not(@foo) and @bar',
				"!\$node->hasAttribute('foo')&&\$node->hasAttribute('bar')"
			],
			[
				'not(@foo and @bar)',
				"!(\$node->hasAttribute('foo')&&\$node->hasAttribute('bar'))",
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						// Not exactly sure of the oldest version that doesn't segault
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
			[
				".='x'or.='y'or.='z'",
				"\$node->textContent==='x'||\$node->textContent==='y'||\$node->textContent==='z'"
			],
			[
				"contains(@foo,'x')",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			],
			[
				" contains( @foo , 'x' ) ",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			],
			[
				"not(contains(@id, 'bar'))",
				"(strpos(\$node->getAttribute('id'),'bar')===false)"
			],
			[
				"starts-with(@foo,'bar')",
				"(strpos(\$node->getAttribute('foo'),'bar')===0)"
			],
			[
				'@foo and (@bar or @baz)',
				"\$node->hasAttribute('foo')&&(\$node->hasAttribute('bar')||\$node->hasAttribute('baz'))",
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
			[
				'(@a = @b) or (@b = @c)',
				"(\$node->getAttribute('a')===\$node->getAttribute('b'))||(\$node->getAttribute('b')===\$node->getAttribute('c'))",
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
			// Custom representations
			[
				"contains('upperlowerdecim',substring(@type,1,5))",
				"strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false"
			],
		];
	}
}