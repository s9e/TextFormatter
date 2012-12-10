<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use stdClass;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ConfigHelper
*/
class ConfigHelperTest extends Test
{
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
	* @testdox toArray() omits empty Collections from values
	*/
	public function testEmptyCollection()
	{
		$original = array('foo' => 1, 'bar' => new Collection);
		$expected = array('foo' => 1);

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox Built-in attribute filter #int is replaced by its callback s9e\TextFormatter\Parser\BuiltInFilters::filterInt
	*/
	public function testBuiltIn()
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
	* @testdox Custom attribute filter #foo is replaced by its registered callback
	*/
	public function testCustom()
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
	* @testdox Variables set for an attribute filter are added to the custom filter without overwriting the variables set for the custom filter
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
}

class ConfigProviderDummy implements ConfigProvider
{
	public function asConfig()
	{
		return array('foo' => 42);
	}
}