<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor
*/
class XPathConvertorTest extends Test
{
	/**
	* @dataProvider getConvertXPathTests
	* @testdox convertXPath() tests
	*/
	public function testConvertXPath($original, $expected, $setup = null)
	{
		$convertor = new XPathConvertor;

		if (isset($setup))
		{
			$setup($convertor);
		}

		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertConditionTests
	* @testdox convertCondition() tests
	*/
	public function testConvertCondition($original, $expected, $setup = null)
	{
		$convertor = new XPathConvertor;

		if (isset($setup))
		{
			$setup($convertor);
		}

		$this->assertSame($expected, $convertor->convertCondition($original));
	}

	public function getConvertXPathTests()
	{
		$_this = $this;

		return array(
			array(
				'"',
				new RuntimeException('Unterminated string literal')
			),
			array(
				'@bar',
				"\$node->getAttribute('bar')"
			),
			array(
				'.',
				"\$node->textContent"
			),
			array(
				'$foo',
				"\$this->params['foo']"
			),
			array(
				"'foo'",
				"'foo'"
			),
			array(
				'"foo"',
				"'foo'"
			),
			array(
				'local-name()',
				'$node->localName'
			),
			array(
				'name()',
				'$node->nodeName'
			),
			array(
				'123',
				"'123'"
			),
			array(
				'normalize-space(@bar)',
				"\$this->xpath->evaluate('normalize-space(@bar)',\$node)"
			),
			array(
				'string-length(@bar)',
				"strlen(preg_replace('(.)us','.',\$node->getAttribute('bar')))"
			),
			array(
				'string-length(@bar)',
				"mb_strlen(\$node->getAttribute('bar'),'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'string-length()',
				"strlen(preg_replace('(.)us','.',\$node->textContent))"
			),
			array(
				'string-length()',
				"mb_strlen(\$node->textContent,'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'substring(.,1,2)',
				"\$this->xpath->evaluate('substring(.,1,2)',\$node)"
			),
			array(
				'substring(.,1,2)',
				"mb_substr(\$node->textContent,0,2,'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'substring(.,0,2)',
				"\$this->xpath->evaluate('substring(.,0,2)',\$node)"
			),
			array(
				// NOTE: as per XPath specs, the length is adjusted to the negative position
				'substring(.,0,2)',
				"mb_substr(\$node->textContent,0,1,'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'substring(.,@x,1)',
				"\$this->xpath->evaluate('substring(.,@x,1)',\$node)"
			),
			array(
				'substring(.,@x,1)',
				"mb_substr(\$node->textContent,max(0,\$node->getAttribute('x')-1),1,'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'substring(.,1,@x)',
				"\$this->xpath->evaluate('substring(.,1,@x)',\$node)"
			),
			array(
				'substring(.,1,@x)',
				"mb_substr(\$node->textContent,0,max(0,\$node->getAttribute('x')),'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'substring(.,2)',
				"\$this->xpath->evaluate('substring(.,2)',\$node)"
			),
			array(
				'substring(.,2)',
				"mb_substr(\$node->textContent,1,134217726,'utf-8')",
				function ($convertor)
				{
					$convertor->useMultibyteStringFunctions = true;
				}
			),
			array(
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			),
			array(
				'translate(@bar,"abc","ABC")',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			),
			array(
				'translate(@bar,"éè","ÉÈ")',
				"strtr(\$node->getAttribute('bar'),array('é'=>'É','è'=>'È'))"
			),
			array(
				'translate(@bar,"ab","ABC")',
				"strtr(\$node->getAttribute('bar'),'ab','AB')"
			),
			array(
				'translate(@bar,"abcd","AB")',
				"strtr(\$node->getAttribute('bar'),array('a'=>'A','b'=>'B','c'=>'','d'=>''))"
			),
			array(
				'translate(@bar,"abbd","ABCD")',
				"strtr(\$node->getAttribute('bar'),'abd','ABD')"
			),
			// Custom representations
			array(
				"substring('songWw',6-5*boolean(@songid),5)",
				"(\$node->hasAttribute('songid')?'songW':'w')"
			),
			array(
				'400-360*boolean(@songid)',
				"(\$node->hasAttribute('songid')?40:400)"
			),
			// Math
			array(
				'@foo + 12',
				"\$node->getAttribute('foo')+12",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				'44 + $bar',
				"44+\$this->params['bar']",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				'@h * 3600 + @m * 60 + @s',
				"\$node->getAttribute('h')*3600+\$node->getAttribute('m')*60+\$node->getAttribute('s')",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
		);
	}

	public function getConvertConditionTests()
	{
		$_this = $this;

		return array(
			array(
				'@foo',
				"\$node->hasAttribute('foo')"
			),
			array(
				'not(@foo)',
				"!\$node->hasAttribute('foo')"
			),
			array(
				'$foo',
				"!empty(\$this->params['foo'])"
			),
			array(
				'not($foo)',
				"empty(\$this->params['foo'])"
			),
			array(
				".='foo'",
				"\$node->textContent==='foo'"
			),
			array(
				"@foo='foo'",
				"\$node->getAttribute('foo')==='foo'"
			),
			array(
				".='fo\"o'",
				"\$node->textContent==='fo\"o'"
			),
			array(
				'.=\'"_"\'',
				'$node->textContent===\'"_"\''
			),
			array(
				".='foo'or.='bar'",
				"\$node->textContent==='foo'||\$node->textContent==='bar'"
			),
			array(
				'.=3',
				"\$node->textContent==3"
			),
			array(
				'.=022',
				"\$node->textContent==22"
			),
			array(
				'044=.',
				"44==\$node->textContent"
			),
			array(
				'@foo != @bar',
				"\$node->getAttribute('foo')!==\$node->getAttribute('bar')"
			),
			array(
				'@foo = @bar or @baz',
				"\$node->getAttribute('foo')===\$node->getAttribute('bar')||\$node->hasAttribute('baz')"
			),
			array(
				'not(@foo) and @bar',
				"!\$node->hasAttribute('foo')&&\$node->hasAttribute('bar')"
			),
			array(
				'not(@foo and @bar)',
				"!(\$node->hasAttribute('foo')&&\$node->hasAttribute('bar'))",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						// Not exactly sure of the oldest version that doesn't segault
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				".='x'or.='y'or.='z'",
				"\$node->textContent==='x'||\$node->textContent==='y'||\$node->textContent==='z'"
			),
			array(
				"contains(@foo,'x')",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			),
			array(
				" contains( @foo , 'x' ) ",
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			),
			array(
				"not(contains(@id, 'bar'))",
				"(strpos(\$node->getAttribute('id'),'bar')===false)",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				"starts-with(@foo,'bar')",
				"(strpos(\$node->getAttribute('foo'),'bar')===0)"
			),
			array(
				'@foo and (@bar or @baz)',
				"\$node->hasAttribute('foo')&&(\$node->hasAttribute('bar')||\$node->hasAttribute('baz'))",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				'(@a = @b) or (@b = @c)',
				"(\$node->getAttribute('a')===\$node->getAttribute('b'))||(\$node->getAttribute('b')===\$node->getAttribute('c'))",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			// Custom representations
			array(
				"contains('upperlowerdecim',substring(@type,1,5))",
				"strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false"
			),
		);
	}
}