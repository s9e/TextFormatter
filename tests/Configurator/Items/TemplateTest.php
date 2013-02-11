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
	* @testdox Constructor accepts a string
	*/
	public function testAcceptsString()
	{
		new Template('');
	}

	/**
	* @testdox Constructor accepts a valid callback
	*/
	public function testAcceptsCallback()
	{
		new Template(function(){});
	}

	/**
	* @testdox Constructor throws an exception on anything else
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a string or a valid callback
	*/
	public function testRejectsEverythingElse()
	{
		new Template(false);
	}

	/**
	* @testdox When cast as string, returns the string passed to constructor if applicable
	*/
	public function testToStringString()
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

	/**
	* @testdox Constructor interprets a string as a literal, even if it would be a valid callback
	*/
	public function testConstructorStringLiteral()
	{
		$template = new Template('uniqid');

		$this->assertSame('uniqid', (string) $template);
	}

	/**
	* @testdox getParameters() returns the list of parameters used in this template
	*/
	public function testGetParameters()
	{
		$template = new Template('<div><xsl:value-of select="$L_FOO"/></div>');

		$this->assertSame(
			['L_FOO'],
			$template->getParameters()
		);
	}
}