<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\TemplateCollection;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TemplateCollection
*/
class TemplateCollectionTest extends Test
{
	public function setUp()
	{
		$this->tag = new Tag;
		$this->templates = new TemplateCollection($this->tag);
	}

	/**
	* @testdox set() accepts a string and returns an instance of Template
	*/
	public function testSetString()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Template',
			$this->templates->set('', 'foo')
		);
	}

	/**
	* @testdox set() accepts a callback and returns an instance of Template
	*/
	public function testSetCallback()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Template',
			$this->templates->set('', function(){})
		);
	}

	/**
	* @testdox set() accepts an instance of Template, which it returns
	*/
	public function testSetInstance()
	{
		$template = new Template('foo');
		$this->assertSame(
			$template,
			$this->templates->set('', $template)
		);
	}

	/**
	* @testdox set() optimizes string templates
	*/
	public function testSetOptimize()
	{
		$this->templates->set('', '<b >foo</b >');

		$this->assertEquals(
			'<b>foo</b>',
			$this->templates->get('')
		);
	}

	/**
	* @testdox set() checks string templates for unsafe content
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage The template contains a 'disable-output-escaping' attribute
	*/
	public function testSetCheckUnsafe()
	{
		$this->templates->set('', '<b disable-output-escaping="1"><xsl:apply-templates/></b>');
	}
}