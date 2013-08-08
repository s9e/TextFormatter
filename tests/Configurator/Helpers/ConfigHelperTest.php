<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use stdClass;
use Traversable;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ConfigHelper
*/
class ConfigHelperTest extends Test
{
	/**
	* @testdox filterVariants() filters the right variant
	*/
	public function testFilterVariants()
	{
		$foo = new Variant(0);
		$foo->set('variant', 1);

		$config = ['foo' => $foo];

		ConfigHelper::filterVariants($config, 'variant');

		$this->assertSame(
			['foo' => 1],
			$config
		);
	}

	/**
	* @testdox filterVariants() recurses into deep arrays
	*/
	public function testFilterVariantsDeep()
	{
		$config = [
			'foo' => new Variant(42),
			'bar' => ['baz' => new Variant(55)]
		];

		ConfigHelper::filterVariants($config);

		$this->assertSame(
			[
				'foo' => 42,
				'bar' => ['baz' => 55]
			],
			$config
		);
	}

	/**
	* @testdox filterVariants() recurses into variants
	*/
	public function testFilterVariantsRecursive()
	{
		$foo = new Variant(0);
		$bar = new Variant(0);

		$foo->set('vv', ['bar' => $bar]);
		$bar->set('vv', ['baz' => 42]);

		$config = ['foo' => $foo];

		ConfigHelper::filterVariants($config, 'vv');

		$this->assertSame(
			['foo' => ['bar' => ['baz' => 42]]],
			$config
		);
	}

	/**
	* @testdox filterVariants() removes NULL variants
	*/
	public function testFilterVariantsNull()
	{
		$foo = new Variant;
		$foo->set('foo', 42);

		$config = ['foo' => $foo];

		ConfigHelper::filterVariants($config, 'vv');

		$this->assertSame(
			[],
			$config
		);
	}

	/**
	* @testdox generateQuickMatchFromList() returns the longest common substring of a list of strings
	*/
	public function testGenerateQuickMatch()
	{
		$this->assertSame(
			'xxx',
			ConfigHelper::generateQuickMatchFromList([
				'xxx12345d',
				'xxx54321d'
			])
		);
	}

	/**
	* @testdox generateQuickMatchFromList() returns a string even if it contains only digit characters
	*/
	public function testGenerateQuickMatchNumbers()
	{
		$this->assertSame(
			'123',
			ConfigHelper::generateQuickMatchFromList([
				'01234',
				'123a'
			])
		);
	}

	/**
	* @testdox generateQuickMatchFromList() returns FALSE if no common substring is found
	*/
	public function testGenerateQuickMatchFalse()
	{
		$this->assertFalse(
			ConfigHelper::generateQuickMatchFromList([
				':)',
				';)',
				':('
			])
		);
	}

	/**
	* @testdox generateQuickMatchFromList() compares strings as bytes and returns a binary string
	*/
	public function testGenerateQuickMatchBinary()
	{
		$this->assertSame(
			"\xA9\xC3",
			ConfigHelper::generateQuickMatchFromList([
				'©ö',
				'éô'
			])
		);
	}

	/**
	* @testdox toArray() works with deep arrays
	*/
	public function testDeepArrays()
	{
		$arr = [
			'foo' => ['foo1' => 4, 'foo2' => 5],
			'bar' => [1, 2, 3],
			'baz' => 42
		];

		$this->assertEquals($arr, ConfigHelper::toArray($arr));
	}

	/**
	* @testdox toArray() calls asConfig() for objects in deep arrays that implement ConfigProvider
	*/
	public function testConfigProviderDeep()
	{
		$arr = [
			'foo' => ['foo1' => new ConfigProviderDummy],
			'bar' => new ConfigProviderDummy
		];

		$this->assertEquals(
			[
				'foo' => ['foo1' => ['foo' => 42]],
				'bar' => ['foo' => 42]
			],
			ConfigHelper::toArray($arr)
		);
	}

	/**
	* @testdox toArray() throws an exception for objects in deep arrays that are not Traversable and do not implement ConfigProvider
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot convert an instance of stdClass to array
	*/
	public function testInvalidObject()
	{
		ConfigHelper::toArray([new stdClass]);
	}

	/**
	* @testdox toArray() omits empty arrays from values
	*/
	public function testEmptyArray()
	{
		$original = ['foo' => [1], 'bar' => []];
		$expected = ['foo' => [1]];

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox toArray() preserves empty arrays if its second argument is TRUE
	*/
	public function testKeepEmptyArray()
	{
		$original = ['foo' => [1], 'bar' => []];
		$expected = $original;

		$this->assertSame($expected, ConfigHelper::toArray($original, true));
	}

	/**
	* @testdox toArray() preserves empty arrays in deep arrays if its second argument is TRUE
	*/
	public function testKeepEmptyArrayDeep()
	{
		$original = [['bar' => []]];
		$expected = $original;

		$this->assertSame($expected, ConfigHelper::toArray($original, true));
	}

	/**
	* @testdox toArray() omits NULL values
	*/
	public function testNull()
	{
		$original = ['foo' => [1], 'bar' => null];
		$expected = ['foo' => [1]];

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox toArray() preserves NULL values if its third argument is TRUE
	*/
	public function testKeepNull()
	{
		$original = ['foo' => [1], 'bar' => null];
		$expected = $original;

		$this->assertSame($expected, ConfigHelper::toArray($original, false, true));
	}

	/**
	* @testdox toArray() preserves NULL values in deep arrays if its third argument is TRUE
	*/
	public function testKeepNullDeep()
	{
		$original = [['bar' => null]];
		$expected = $original;

		$this->assertSame($expected, ConfigHelper::toArray($original, false, true));
	}

	/**
	* @testdox optimizeArray() reduces the size of a serialized config
	*/
	public function testOptimizeArraySmaller()
	{
		// Create a Configurator and load a few of BBCodes
		$configurator = new Configurator;
		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('I');
		$configurator->BBCodes->addFromRepository('U');

		$normalConfig = $configurator->asConfig();
		ConfigHelper::filterVariants($normalConfig);

		$optimizedConfig = $normalConfig;
		ConfigHelper::optimizeArray($optimizedConfig);

		$this->assertEquals($normalConfig, $optimizedConfig);
		$this->assertLessThan(
			strlen(serialize($normalConfig)),
			strlen(serialize($optimizedConfig))
		);
	}
}

class ConfigProviderDummy implements ConfigProvider
{
	public function asConfig()
	{
		return ['foo' => 42];
	}
}