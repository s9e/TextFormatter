<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderer
*/
class RendererTest extends Test
{
	/**
	* @testdox loadXML() returns a DOMDocument
	*/
	public function testLoadXML()
	{
		$renderer = new DummyRenderer;

		$this->assertInstanceOf(
			'DOMDocument',
			$renderer->__call('loadXML', ['<x/>'])
		);
	}

	/**
	* @testdox render() throws an exception on invalid XML with a "r" root tag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot load XML: Premature end of data in tag r
	*/
	public function testInvalidXMLRich()
	{
		$this->configurator->rendering->getRenderer()->render('<r>');
	}

	/**
	* @testdox render() throws an exception on truncated XML with a "t" root tag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot load XML: Premature end of data in tag t
	*/
	public function testInvalidXMLPlain()
	{
		$this->configurator->rendering->getRenderer()->render('<t>');
	}
}

class DummyRenderer extends Renderer
{
	protected function renderRichText($xml) {}
	public function setParameter($paramName, $paramValue) {}
	public function __call($methodName, $args)
	{
		return call_user_func_array('parent::' . $methodName, $args);
	}
}