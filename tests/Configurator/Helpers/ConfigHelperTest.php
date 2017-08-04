<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use stdClass;
use Traversable;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ConfigHelper
*/
class ConfigHelperTest extends Test
{
	/**
	* @testdox filterConfig() filters for the right target
	*/
	public function testFilterConfig()
	{
		foreach (['PHP', 'JS'] as $target)
		{
			$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\FilterableConfigValue')->getMock();
			$mock->expects($this->once())
			     ->method('filterConfig')
			     ->with($target)
			     ->will($this->returnValue(42));

			$this->assertSame(
				['foo' => 42],
				ConfigHelper::filterConfig(['foo' => $mock], $target)
			);
		}
	}

	/**
	* @testdox filterConfig() recurses into deep arrays
	*/
	public function testFilterConfigDeep()
	{
		$config = [
			'foo' => new DummyConfigValue(42),
			'bar' => ['baz' => new DummyConfigValue(55)]
		];

		$this->assertSame(
			[
				'foo' => 42,
				'bar' => ['baz' => 55]
			],
			ConfigHelper::filterConfig($config, 'PHP')
		);
	}

	/**
	* @testdox filterConfig() recurses with the correct target
	*/
	public function testFilterConfigRecursive()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\FilterableConfigValue')->getMock();
		$mock->expects($this->once())
		     ->method('filterConfig')
		     ->with('JS')
		     ->will($this->returnValue(42));

		$original = ['foo' => ['bar' => $mock]];
		$expected = ['foo' => ['bar' => 42]];

		$this->assertSame(
			$expected,
			ConfigHelper::filterConfig($original, 'JS')
		);
	}

	/**
	* @testdox filterConfig() ignores instances of FilterableConfigValue that return NULL
	*/
	public function testFilterConfigNull()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\FilterableConfigValue')->getMock();
		$mock->expects($this->once())
		     ->method('filterConfig')
		     ->will($this->returnValue(null));

		$original = ['foo' => $mock, 'bar' => 42];
		$expected = ['bar' => 42];

		$this->assertSame(
			$expected,
			ConfigHelper::filterConfig($original, 'PHP')
		);
	}

	/**
	* @testdox filterConfig() preserves instances of Dictionary with an array if the variant is JS
	*/
	public function testFilterConfigDictionaryJS()
	{
		$config = ['dict' => new Dictionary(['foo' => 'bar'])];
		ConfigHelper::filterConfig($config, 'JS');

		$this->assertEquals(
			['dict' => new Dictionary(['foo' => 'bar'])],
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
	* @testdox toArray() omits NULL values from asConfig() calls
	*/
	public function testAsConfigNull()
	{
		$original = ['foo' => [1], 'bar' => new NullConfigProvider];
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

		$configurator->finalize();

		$normalConfig = $configurator->asConfig();
		ConfigHelper::filterConfig($normalConfig);

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

class NullConfigProvider implements ConfigProvider
{
	public function asConfig()
	{
		return null;
	}
}

class DummyConfigValue implements FilterableConfigValue
{
	protected $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public function filterConfig($target)
	{
		return $this->value;
	}
}