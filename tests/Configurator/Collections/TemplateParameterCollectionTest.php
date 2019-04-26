<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection
*/
class TemplateParameterCollectionTest extends Test
{
	/**
	* @testdox add('foo') adds parameter 'foo' with an empty value
	*/
	public function testAddNoValue()
	{
		$collection = new TemplateParameterCollection;
		$collection->add('foo');

		$this->assertSame('', $collection->get('foo'));
	}

	/**
	* @testdox add('foo', 1) adds parameter 'foo' with value '1'
	*/
	public function testAdd()
	{
		$collection = new TemplateParameterCollection;
		$collection->add('foo', 1);

		$this->assertSame('1', $collection->get('foo'));
	}

	/**
	* @testdox add('foo bar') throws an exception
	*/
	public function testAddInvalid()
	{
		$this->expectException('InvalidArgumentException');

		$collection = new TemplateParameterCollection;
		$collection->add('foo bar');
	}
}