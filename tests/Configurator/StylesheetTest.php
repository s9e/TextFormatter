<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Stylesheet
*/
class StylesheetTest extends Test
{
	/**
	* @testdox setOutputMethod('xml') sets the stylesheet's output method to 'xml'
	*/
	public function testSetOutputMethod()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('xml');

		$this->assertContains(' method="xml"', $stylesheet->get());
	}

	/**
	* @testdox setOutputMethod('text') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Only html and xml methods are supported
	*/
	public function testSetOutputMethodInvalid()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('text');
	}
}