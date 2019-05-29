<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use Exception;
use ReflectionMethod;
use ReflectionProperty;
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
	public function testConvertXPath($original, $expected)
	{
		$convertor = new XPathConvertor;
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}
		$this->assertSame($expected, $convertor->convertXPath($original));
	}

	/**
	* @dataProvider getConvertConditionTests
	* @testdox convertCondition() tests
	*/
	public function testConvertCondition($original, $expected)
	{
		$convertor = new XPathConvertor;
		$this->assertSame($expected, $convertor->convertCondition($original));
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
				'123'
			],
			[
				'substring-after(@foo,@bar)',
				"\$this->xpath->evaluate('substring-after(@foo,@bar)',\$node)"
			],
			[
				'translate(@foo,@bar)',
				"\$this->xpath->evaluate('translate(@foo,@bar)',\$node)"
			],
			[
				'//X[@a = current()/@a]',
				"\$this->xpath->evaluate('string(//X[@a = '.\$node->getNodePath().'/@a])',\$node)"
			],
			[
				'0',
				'0'
			],
			[
				'0777',
				'777'
			],
			[
				'-0777',
				'-777'
			],
			[
				'string-length(@bar)',
				"preg_match_all('(.)su',\$node->getAttribute('bar'))"
			],
			[
				'string-length()',
				"preg_match_all('(.)su',\$node->textContent)"
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
			[
				'substring-after(@foo,"/")',
				"substr(strstr(\$node->getAttribute('foo'),'/'),1)"
			],
			[
				'substring-after(@foo,"&amp;")',
				"substr(strstr(\$node->getAttribute('foo'),'&amp;'),5)"
			],
			[
				'substring-before(@foo,"/")',
				"strstr(\$node->getAttribute('foo'),'/',true)"
			],
			[
				'substring-before(@foo,@bar)',
				"strstr(\$node->getAttribute('foo'),\$node->getAttribute('bar'),true)"
			],
			// Math
			[
				'@foo + 12',
				"\$node->getAttribute('foo')+12"
			],
			[
				'44 + $bar',
				"44+\$this->params['bar']"
			],
			[
				'@h * 3600 + @m * 60 + @s',
				"\$node->getAttribute('h')*3600+\$node->getAttribute('m')*60+\$node->getAttribute('s')"
			],
			[
				'@x div@y',
				"\$node->getAttribute('x')/\$node->getAttribute('y')"
			],
			[
				'(@height + 49)',
				"(\$node->getAttribute('height')+49)"
			],
			[
				'100 * (@height + 49) div @width',
				"100*(\$node->getAttribute('height')+49)/\$node->getAttribute('width')"
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
				"\$this->params['foo']!==''"
			],
			[
				'not($foo)',
				"\$this->params['foo']===''"
			],
			[
				'@width > 0',
				"\$node->getAttribute('width')>0"
			],
			[
				'@*',
				'$node->attributes->length'
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
				'.=0',
				"\$node->textContent==0"
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
				"!(\$node->hasAttribute('foo')&&\$node->hasAttribute('bar'))"
			],
			[
				".='x'or.='y'or.='z'",
				"\$node->textContent==='x'||\$node->textContent==='y'||\$node->textContent==='z'"
			],
			[
				"@x and @y and @z and @a",
				"\$node->hasAttribute('x')&&\$node->hasAttribute('y')&&\$node->hasAttribute('z')&&\$node->hasAttribute('a')"
			],
			[
				"@type='gifv' and @width and @height and @height != 0",
				"\$node->getAttribute('type')==='gifv'&&\$node->hasAttribute('width')&&\$node->hasAttribute('height')&&\$node->getAttribute('height')!=0"
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
				"\$node->hasAttribute('foo')&&(\$node->hasAttribute('bar')||\$node->hasAttribute('baz'))"
			],
			[
				'(@a = @b) or (@b = @c)',
				"(\$node->getAttribute('a')===\$node->getAttribute('b'))||(\$node->getAttribute('b')===\$node->getAttribute('c'))"
			],
			[
				'ancestor::foo',
				"\$this->xpath->evaluate('boolean(ancestor::foo)',\$node)"
			],
			[
				"starts-with(@type,'decimal-') or starts-with(@type,'lower-') or starts-with(@type,'upper-')",
				"(strpos(\$node->getAttribute('type'),'decimal-')===0)||(strpos(\$node->getAttribute('type'),'lower-')===0)||(strpos(\$node->getAttribute('type'),'upper-')===0)"
			],
			[
				'@tld="es" and $AMAZON_ASSOCIATE_TAG_ES',
				"\$node->getAttribute('tld')==='es'&&\$this->params['AMAZON_ASSOCIATE_TAG_ES']!==''"
			],
			[
				'@tld="es"and$AMAZON_ASSOCIATE_TAG_ES',
				"\$node->getAttribute('tld')==='es'&&\$this->params['AMAZON_ASSOCIATE_TAG_ES']!==''"
			],
			[
				'concat(foo/@bar, $PARAM)',
				"\$this->xpath->evaluate('concat(foo/@bar, '.\$this->getParamAsXPath('PARAM').')',\$node)"
			],
		];
	}
}