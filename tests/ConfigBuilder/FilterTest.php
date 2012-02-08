<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Filter;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\Filter
*/
class FilterTest extends Test
{
	/**
	* @testdox __construct($callback) throws a InvalidArgumentException if $callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback 'foobarbaz' is not callable
	*/
	public function testConstructor()
	{
		new Filter('foobarbaz');
	}
}