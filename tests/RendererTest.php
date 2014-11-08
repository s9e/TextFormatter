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
			$renderer->__call('loadXML', array('<x/>'))
		);
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