<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Plugins\Emoticons\EmoticonCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\EmoticonCollection
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

	/**
	* @testdox Throws an exception when an unsafe template is set
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	*/
	public function testUnsafe()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<xsl:copy />');
	}
}