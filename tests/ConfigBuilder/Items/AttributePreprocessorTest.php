<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;
use s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor
*/
class AttributePreprocessorTest extends Test
{
	/**
	* @testdox __construct() throws an InvalidArgumentException if the regexp is not valid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regular expression
	*/
	public function testInvalidRegexp()
	{
		new AttributePreprocessor('(?)');
	}
}