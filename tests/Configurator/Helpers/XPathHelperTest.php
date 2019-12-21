<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\XPathHelper
*/
class XPathHelperTest extends Test
{
	/**
	* @testdox getVariables() tests
	* @dataProvider getGetVariablesTests
	*/
	public function testGetVariables($expr, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$this->assertSame($expected, XPathHelper::getVariables($expr));
	}

	public function getGetVariablesTests()
	{
		return [
			[
				'',
				[]
			],
			[
				'$foo or $baz',
				['baz', 'foo']
			],
			[
				'$foo or "$baz"',
				['foo']
			],
			[
				'"$foo or $baz"',
				[]
			],
		];
	}

	/**
	* @testdox isExpressionNumeric() tests
	* @dataProvider getIsExpressionNumericTests
	*/
	public function testIsExpressionNumeric($expected, $expr)
	{
		$this->assertSame($expected, XPathHelper::isExpressionNumeric($expr));
	}

	public function getIsExpressionNumericTests()
	{
		return [
			[false, '@foo'],
			[true,  '1+@foo'],
			[true,  '@foo+1'],
			[true,  '1-@foo'],
			[false, '@foo-1'],
			[true,  '@foo + @bar'],
			[true,  '$foo + $bar'],
			[true,  '$foo + 0'],
			[true,  '$foo * 3'],
			[true,  '@x * 3'],
			[true,  '@x * -.3'],
			[true,  '@x * -3.14'],
			[true,  '@x div @y'],
			[true,  '@height*100div@width'],
			[true,  '(@height+100)*@width'],
			[false, 'foo(@height+100)'],
			[false, 'foo ( @height + 100 )'],
			[false, 'foodiv ( @height + 100 )'],
			[true,  '@foo div (1+1)'],
			[true,  '100*(49+@height)div@width'],
		];
	}

	/**
	* @testdox minify() tests
	* @dataProvider getMinifyTests
	*/
	public function testMinify($original, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$this->assertSame($expected, XPathHelper::minify($original));
	}

	public function getMinifyTests()
	{
		return [
			[
				'',
				''
			],
			[
				' @foo ',
				'@foo'
			],
			[
				'@ foo',
				'@foo'
			],
			[
				'concat(@foo, @bar, @baz)',
				'concat(@foo,@bar,@baz)'
			],
			[
				"concat(@foo, ' @bar ', @baz)",
				"concat(@foo,' @bar ',@baz)"
			],
			[
				'@foo = 2',
				'@foo=2'
			],
			[
				'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))',
				'substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))'
			],
			[
				'@foo - bar = 2',
				'@foo -bar=2'
			],
			[
				'@foo- - 1 = 2',
				'@foo- -1=2'
			],
			[
				' foo or _bar ',
				'foo or _bar'
			],
			[
				'foo = "bar',
				new RuntimeException("Cannot parse XPath expression 'foo = \"bar'")
			],
			[
				'100 * (315 + 30) div 560',
				'100*(315+30)div560'
			],
			[
				'@div or @div',
				'@div or@div'
			],
			[
				'333 div 111',
				'333div111'
			],
			[
				'a1 - 1',
				'a1 -1'
			],
			[
				'12 - 5',
				'12-5'
			],
		];
	}

	/**
	* @dataProvider getParseEqualityExprTests
	*/
	public function testParseEqualityExpr($expr, $expected)
	{
		$this->assertSame($expected, XPathHelper::parseEqualityExpr($expr));
	}

	public function getParseEqualityExprTests()
	{
		return [
			[
				'@foo != "bar"',
				false
			],
			[
				'@foo = "bar"',
				['@foo' => ['bar']]
			],
			[
				'@foo = "bar" or @foo = "baz"',
				['@foo' => ['bar', 'baz']]
			],
			[
				'"bar" = @foo or \'baz\' = @foo',
				['@foo' => ['bar', 'baz']]
			],
			[
				'$foo = "bar"',
				['$foo' => ['bar']]
			],
			[
				'.="bar"or.="baz"or.="quux"',
				['.' => ['bar', 'baz', 'quux']]
			],
			[
				'$foo = concat("bar", \'baz\')',
				['$foo' => ['barbaz']]
			],
			[
				'$a = "aa" or $b = "bb"',
				['$a' => ['aa'], '$b' => ['bb']]
			],
		];
	}

	/**
	* @testdox encodeStrings() works
	*/
	public function testEncodeStrings()
	{
		$original = '\'foo\' = "foo" or \'bar\' = "bar"';
		$expected = '\'666f6f\' = "666f6f" or \'626172\' = "626172"';

		$this->assertEquals($expected, XPathHelper::encodeStrings($original));
	}

	/**
	* @testdox decodeStrings() works
	*/
	public function testDecodeStrings()
	{
		$original = '\'666f6f\' = "666f6f" or \'626172\' = "626172"';
		$expected = '\'foo\' = "foo" or \'bar\' = "bar"';

		$this->assertEquals($expected, XPathHelper::decodeStrings($original));
	}
}