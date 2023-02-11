<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use DOMXPath;
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

	public static function getGetVariablesTests()
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

	public static function getIsExpressionNumericTests()
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
			[true,  '400-200*boolean(@foo)'],
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

		// Ensure the expected result is valid
		if (is_string($expected) && $expected !== '')
		{
			$xpath = new DOMXPath(new DOMDocument);
			$xpath->evaluate($expected);
		}
	}

	public static function getMinifyTests()
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
				'1 + ( ( ( 1 + 1 ) * 1 ) * 1 )',
				'1+(((1+1)*1)*1)'
			],
			[
				' foo or _bar ',
				'foo or _bar'
			],
			[
				'100 * (315 + 30) div 560',
				'100*(315+30)div 560'
			],
			[
				'@div or @div',
				'@div or@div'
			],
			[
				'333 div 111',
				'333div 111'
			],
			[
				'333 div (1+1)',
				'333div(1+1)'
			],
			[
				'(1 + 1) div 111',
				'(1+1)div 111'
			],
			[
				'a1 - 1',
				'a1 -1'
			],
			[
				'12 - 5',
				'12-5'
			],
			[
				"starts-with(@id, 'episode/') or starts-with(@id, 'show/')",
				"starts-with(@id,'episode/')or starts-with(@id,'show/')"
			],
			[
				'2 * (1 + 1)',
				'2*(1+1)'
			],
			[
				'(1 + 1) * (1 + 1)',
				'(1+1)*(1+1)'
			],
			[
				'2 * ( ( 1 + 1 ) )',
				'2*(1+1)'
			],
			[
				'2 * ( ( ( 1 + 1 ) ) )',
				'2*(1+1)'
			],
			[
				'2 * ( ( ( 1 + 1 ) + ( 1 + 1 ) ) )',
				'2*((1+1)+(1+1))'
			],
			[
				'@foo = 1 or @bar = 2',
				'@foo=1or@bar=2'
			],
			[
				'@foo = a_1 or @bar = 2',
				'@foo=a_1 or@bar=2'
			],
			[
				'@foo = 1 or @bar = 2',
				'@foo=1or@bar=2'
			],
			[
				/**
				* @link https://www.w3.org/TR/1999/REC-xpath-19991116/#exprlex
				*
				* "Otherwise, the token must not be recognized as a MultiplyOperator, an OperatorName, a NodeType, a FunctionName, or an AxisName."
				*/
				'true() and true()',
				'true()and true()'
			],
			[
				'(1+(1+1))',
				'1+(1+1)'
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

	public static function getParseEqualityExprTests()
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