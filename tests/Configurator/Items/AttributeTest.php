<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Int;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Attribute
*/
class AttributeTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$attr = new Attribute(array('isRequired' => false));
		$this->assertFalse($attr->isRequired);

		$attr = new Attribute(array('isRequired' => true));
		$this->assertTrue($attr->isRequired);
	}

	/**
	* @testdox $attr->filterChain can be assigned an array
	*/
	public function testSetFilterChainArray()
	{
		$attr = new Attribute;
		$attr->filterChain = array(new Int, new Url);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\AttributeFilterChain',
			$attr->filterChain
		);

		$this->assertSame(2, count($attr->filterChain), 'Wrong filter count');
	}

	/**
	* @testdox $attr->generator accepts an instance of ProgrammableCallback
	*/
	public function testSetGeneratorProgrammableCallback()
	{
		$attr     = new Attribute;
		$callback = new ProgrammableCallback('mt_rand');

		$attr->generator = $callback;

		$this->assertSame($callback, $attr->generator);
	}

	/**
	* @testdox $attr->generator accepts a callback and normalizes it to an instance of ProgrammableCallback
	*/
	public function testSetGeneratorCallback()
	{
		$attr = new Attribute;
		$attr->generator = 'mt_rand';

		$this->assertInstanceof(
			's9e\\TextFormatter\\Configurator\\Items\\ProgrammableCallback',
			$attr->generator
		);
	}

	/**
	* @testdox asConfig() correctly produces a config array
	*/
	public function testAsConfig()
	{
		$attr = new Attribute;
		$attr->defaultValue = 'foo';

		$this->assertEquals(
			array(
				'defaultValue' => 'foo',
				'required'     => true
			),
			$attr->asConfig()
		);
	}
}