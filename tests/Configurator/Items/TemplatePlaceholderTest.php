<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\TemplatePlaceholder;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\TemplatePlaceholder
*/
class TemplatePlaceholderTest extends Test
{
	/**
	* @testdox Throws an exception if callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be callable
	*/
	public function testConstructor()
	{
		new TemplatePlaceholder(55);
	}

	/**
	* @testdox allowsUnsafeMarkup() returns false by default
	*/
	public function testAllowsUnsafeMarkupDefault()
	{
		$template = new TemplatePlaceholder(function(){});

		$this->assertFalse($template->allowsUnsafeMarkup());
	}

	/**
	* @testdox disableTemplateChecking() allows unsafe markup to be used
	*/
	public function testDisableTemplateChecking()
	{
		$template = new TemplatePlaceholder(function(){});
		$template->disableTemplateChecking();

		$this->assertTrue($template->allowsUnsafeMarkup());
	}

	/**
	* @testdox enableTemplateChecking() re-enables template checking if it was disabled
	*/
	public function testEnableTemplateChecking()
	{
		$template = new TemplatePlaceholder(function(){});
		$template->disableTemplateChecking();
		$template->enableTemplateChecking();

		$this->assertFalse($template->allowsUnsafeMarkup());
	}


	/**
	* @testdox Executes the callback when cast as string and return its result
	*/
	public function testToString()
	{
		$template = new TemplatePlaceholder(
			function ()
			{
				return 'foo';
			}
		);

		$this->assertSame('foo', (string) $template);
	}
}