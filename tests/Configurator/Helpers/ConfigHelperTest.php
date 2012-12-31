<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use stdClass;
use Traversable;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\Javascript\Code;
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

		$config = array('foo' => $foo);

		ConfigHelper::filterVariants($config, 'variant');

		$this->assertSame(
			array('foo' => 1),
			$config
		);
	}

	/**
	* @testdox filterVariants() recurses into deep arrays
	*/
	public function testFilterVariantsDeep()
	{
		$config = array(
			'foo' => new Variant(42),
			'bar' => array('baz' => new Variant(55))
		);

		ConfigHelper::filterVariants($config);

		$this->assertSame(
			array(
				'foo' => 42,
				'bar' => array('baz' => 55)
			),
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

		$foo->set('vv', array('bar' => $bar));
		$bar->set('vv', array('baz' => 42));

		$config = array('foo' => $foo);

		ConfigHelper::filterVariants($config, 'vv');

		$this->assertSame(
			array('foo' => array('bar' => array('baz' => 42))),
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

		$config = array('foo' => $foo);

		ConfigHelper::filterVariants($config, 'vv');

		$this->assertSame(
			array(),
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
			ConfigHelper::generateQuickMatchFromList(array(
				'xxx12345d',
				'xxx54321d'
			))
		);
	}

	/**
	* @testdox generateQuickMatchFromList() returns a string even if it contains only digit characters
	*/
	public function testGenerateQuickMatchNumbers()
	{
		$this->assertSame(
			'123',
			ConfigHelper::generateQuickMatchFromList(array(
				'01234',
				'123a'
			))
		);
	}

	/**
	* @testdox generateQuickMatchFromList() returns FALSE if no common substring is found
	*/
	public function testGenerateQuickMatchFalse()
	{
		$this->assertFalse(
			ConfigHelper::generateQuickMatchFromList(array(
				':)',
				';)',
				':('
			))
		);
	}

	/**
	* @testdox generateQuickMatchFromList() compares strings as bytes and returns a binary string
	*/
	public function testGenerateQuickMatchBinary()
	{
		$this->assertSame(
			"\xA9\xC3",
			ConfigHelper::generateQuickMatchFromList(array(
				'©ö',
				'éô'
			))
		);
	}

	/**
	* @testdox toArray() works with deep arrays
	*/
	public function testDeepArrays()
	{
		$arr = array(
			'foo' => array('foo1' => 4, 'foo2' => 5),
			'bar' => array(1, 2, 3),
			'baz' => 42
		);

		$this->assertEquals($arr, ConfigHelper::toArray($arr));
	}

	/**
	* @testdox toArray() calls asConfig() for objects in deep arrays that implement ConfigProvider
	*/
	public function testConfigProviderDeep()
	{
		$arr = array(
			'foo' => array('foo1' => new ConfigProviderDummy),
			'bar' => new ConfigProviderDummy
		);

		$this->assertEquals(
			array(
				'foo' => array('foo1' => array('foo' => 42)),
				'bar' => array('foo' => 42)
			),
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
		ConfigHelper::toArray(array(new stdClass));
	}

	/**
	* @testdox toArray() omits NULL values
	*/
	public function testNull()
	{
		$original = array('foo' => array(1), 'bar' => null);
		$expected = array('foo' => array(1));

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox toArray() omits empty arrays from values
	*/
	public function testEmptyArray()
	{
		$original = array('foo' => array(1), 'bar' => array());
		$expected = array('foo' => array(1));

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox toArray() keeps empty arrays if its second argument is TRUE
	*/
	public function testKeepEmptyArray()
	{
		$original = array('foo' => array(1), 'bar' => array());

		$this->assertSame($original, ConfigHelper::toArray($original, true));
	}

	/**
	* @testdox replaceBuiltInFilters() replaces built-in attribute filter #int by its callback s9e\TextFormatter\Parser\BuiltInFilters::filterInt
	*/
	public function testBuiltInAttribute()
	{
		$tag  = new Tag;
		$tag->attributes->add('foo')->filterChain->append('#int');

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);

		$this->assertEquals(
			array(
				array(
					'callback' => 's9e\\TextFormatter\\Parser\\BuiltInFilters::filterInt',
					'params'   => array('attrValue' => null)
				)
			),
			$tagsConfig['FOO']['attributes']['foo']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() copies the built-in filters' signatures based on the names of their parameters
	*/
	public function testBuiltInSignature()
	{
		$tag  = new Tag;
		$tag->attributes->add('foo')->filterChain->append('#regexp', array('regexp' => '#foo#'));

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);

		$this->assertEquals(
			array(
				array(
					'callback' => 's9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp',
					'params'   => array('attrValue' => null, '#foo#')
				)
			),
			$tagsConfig['FOO']['attributes']['foo']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() replaces built-in tag filter #filterAttributes by its callback s9e\TextFormatter\Parser::filterAttributes
	*/
	public function testBuiltInTag()
	{
		$tag  = new Tag;
		$tag->filterChain->clear();
		$tag->filterChain->append('#filterAttributes');

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);

		$this->assertEquals(
			array(
				array(
					'callback' => 's9e\\TextFormatter\\Parser::filterAttributes',
					'params'   => array(
						'tag'            => null,
						'tagConfig'      => null,
						'registeredVars' => null,
						'logger'         => null
					)
				)
			),
			$tagsConfig['FOO']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() replaces custom attribute filter #foo by its registered callback
	*/
	public function testCustomAttribute()
	{
		$tag  = new Tag;
		$tag->attributes->add('foo')->filterChain->append('#foo');

		$filters = new FilterCollection;
		$filters->add('foo', new ProgrammableCallback('mt_rand'));

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, $filters);

		$this->assertEquals(
			array(
				array(
					'callback' => 'mt_rand'
				)
			),
			$tagsConfig['FOO']['attributes']['foo']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() replaces custom tag filter #foo by its registered callback
	*/
	public function testCustomTag()
	{
		$tag  = new Tag;
		$tag->filterChain->clear();
		$tag->filterChain->append('#foo');

		$filters = new FilterCollection;
		$filters->add('foo', new ProgrammableCallback('mt_rand'));

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, $filters);

		$this->assertEquals(
			array(
				array(
					'callback' => 'mt_rand'
				)
			),
			$tagsConfig['FOO']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() adds variables set for an attribute filter to the custom filter without overwriting the variables set for the custom filter
	*/
	public function testCustomFilterVarsArePreserved()
	{
		$tag  = new Tag;
		$tag->attributes->add('foo')->filterChain->append('#range', array('min' => 2, 'max' => 5));

		$fn = function($min, $max) {};

		$filter = new ProgrammableCallback($fn);
		$filter->addParameterByName('min');
		$filter->addParameterByName('max');

		// Set min to 1, it will overwrite the var that was set when appending the filter
		$filter->setVars(array('min' => 1));

		$filters = new FilterCollection;
		$filters->add('range', $filter);

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, $filters);

		$this->assertEquals(
			array(
				array(
					'callback' => $fn,
					'params'   => array(1, 5)
				)
			),
			$tagsConfig['FOO']['attributes']['foo']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() doesn't choke on an empty filterChain
	*/
	public function testEmptyFilterChain()
	{
		$tag  = new Tag;
		$tag->filterChain->clear();

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);
	}

	/**
	* @testdox replaceBuiltInFilters() doesn't touch normal callbacks expressed as a string
	*/
	public function testStringCallback()
	{
		$tag  = new Tag;
		$tag->attributes->add('foo')->filterChain->append('strtolower');

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);

		$this->assertEquals(
			array(
				array(
					'callback' => 'strtolower',
					'params'   => array('attrValue' => null)
				)
			),
			$tagsConfig['FOO']['attributes']['foo']['filterChain']
		);
	}

	/**
	* @testdox replaceBuiltInFilters() throws an exception on unknown custom filters
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown filter '#null'
	*/
	public function testUnknownFilter()
	{
		$tag  = new Tag;
		$tag->filterChain->append('#null');

		$tagsConfig = array('FOO' => $tag->asConfig());
		ConfigHelper::replaceBuiltInFilters($tagsConfig, new FilterCollection);
	}

	/**
	* @testdox optimizeArray() uses references to deduplicate equal arrays
	*/
	public function testOptimizeArray()
	{
		// Create a Configurator and load a few of BBCodes
		$configurator = new Configurator;
		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('I');
		$configurator->BBCodes->addFromRepository('U');

		$optimizedConfig = $configurator->asConfig();
		ConfigHelper::optimizeArray($optimizedConfig);

		$normalConfig = $configurator->asConfig();

		$this->assertEquals($normalConfig, $optimizedConfig);
		$this->assertLessThan(
			strlen(serialize($normalConfig)),
			strlen(serialize($optimizedConfig))
		);
	}

	/**
	* @testdox removeJavascriptCode() recursively removes all instances of Code from a deep array, in-place
	*/
	public function testRemoveJavascriptCode()
	{
		$arr = array(
			'foo' => new Code(''),
			'bar' => array('baz' => new Code(''), 'quux' => 3)
		);

		ConfigHelper::removeJavascriptCode($arr);

		$this->assertSame(
			array('bar' => array('quux' => 3)),
			$arr
		);
	}
}

class ConfigProviderDummy implements ConfigProvider
{
	public function asConfig()
	{
		return array('foo' => 42);
	}
}