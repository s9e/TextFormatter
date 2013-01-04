<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\StylesheetParameterCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\StylesheetParameterCollection
*/
class StylesheetParameterCollectionTest extends Test
{
	/**
	* @testdox add('foo') adds parameter 'foo' with a NULL value
	*/
	public function testAddNoValue()
	{
		$collection = new StylesheetParameterCollection;
		$collection->add('foo');

		$this->assertNull($collection->get('foo'));
	}

	/**
	* @testdox add('foo', 1) adds parameter 'foo' with value '1'
	*/
	public function testAdd()
	{
		$collection = new StylesheetParameterCollection;
		$collection->add('foo', 1);

		$this->assertSame('1', $collection->get('foo'));
	}
}