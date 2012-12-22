<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Template
*/
class TemplateTest extends Test
{
	/**
	* @testdox Template::__construct() accepts a string
	*/
	public function testAcceptsString()
	{
		new Template('');
	}

	/**
	* @testdox Template::__construct() accepts a callback
	*/
	public function testAcceptsCallback()
	{
		new Template(function(){});
	}

	/**
	* @testdox When cast as string, returns the string passed to constructor if applicable
	*/
	public function testToStringCallback()
	{
		$template = new Template('foo');

		$this->assertSame('foo', (string) $template);
	}

	/**
	* @testdox When cast as string, executes the callback passed to constructor and returns its result if applicable
	*/
	public function testToStringCallback()
	{
		$template = new Template(
			function ()
			{
				return 'foo';
			}
		);

		$this->assertSame('foo', (string) $template);
	}
}