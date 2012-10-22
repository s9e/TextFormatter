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
	* @testdox Throws an exception when an invalid template is set
	* @expectedException s9e\TextFormatter\Generator\Exceptions\InvalidXslException
	*/
	public function testInvalid()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<foo:bar>');
	}

	/**
	* @testdox Throws an exception when an unsafe template is set
	* @expectedException s9e\TextFormatter\Generator\Exceptions\UnsafeTemplateException
	*/
	public function testUnsafe()
	{
		$collection = new EmoticonCollection;
		$collection->set(':)', '<xsl:copy />');
	}
}