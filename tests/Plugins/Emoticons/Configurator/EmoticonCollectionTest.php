<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons\Configurator;

use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection
*/
class EmoticonCollectionTest extends Test
{
	/**
	* @testdox Normalizes HTML templates
	*/
	public function testHTML()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<img src="foo.png">');

		$this->assertSame('<img src="foo.png"/>', $collection->get(':)'));
	}

	/**
	* @testdox Throws an exception when an invalid template is set
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
	*/
	public function testInvalid()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<xsl:foo>');
	}
}