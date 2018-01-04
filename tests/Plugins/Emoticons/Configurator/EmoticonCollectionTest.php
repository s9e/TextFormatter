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
	* @expectedException RuntimeException
	*/
	public function testInvalid()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<xsl:foo>');
	}

	/**
	* @testdox Replaces duplicates by default
	*/
	public function testDuplicateDefault()
	{
		$collection = new EmoticonCollection;
		$emoticon1 = $collection->add(':)', ':(');
		$emoticon2 = $collection->add(':)', ':)');

		$this->assertSame($emoticon2, $collection->get(':)'));
		$this->assertNotSame($emoticon1, $emoticon2);
	}

	/**
	* @testdox Throws an meaningful exception message when creating an emoticon that already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Emoticon ':)' already exists
	*/
	public function testDuplicateError()
	{
		$collection = new EmoticonCollection;
		$collection->onDuplicate('error');
		$collection->add(':)', ':(');
		$collection->add(':)', ':)');
	}

	/**
	* @testdox Has a customized exception message on uninitialized access
	* @expectedException RuntimeException
	* @expectedExceptionMessage Emoticon ':)' does not exist
	*/
	public function testExceptionMissing()
	{
		$collection = new EmoticonCollection;
		$collection->get(':)');
	}
}