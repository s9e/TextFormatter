<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TemplateCollection;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TemplateCollection
*/
class TemplateCollectionTest extends Test
{
	/**
	* @testdox set() accepts a string and returns an instance of Template
	*/
	public function testSetString()
	{
		$templates = new TemplateCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Template',
			$templates->set('', 'foo')
		);
	}

	/**
	* @testdox set() accepts an instance of Template, which it returns
	*/
	public function testSetInstance()
	{
		$templates = new TemplateCollection;
		$template  = new Template('foo');

		$this->assertSame(
			$template,
			$templates->set('', $template)
		);
	}
}