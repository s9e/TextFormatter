<?php

namespace s9e\TextFormatter\Tests\Configurator;

use DOMDocument;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
*/
class UnsafeTemplateExceptionTest extends Test
{
	/**
	* @testdox getNode() returns the stored node
	*/
	public function testGetNode()
	{
		$dom = new DOMDocument;
		$dom->loadXML('<foo/>');
		$exception = new UnsafeTemplateException('Msg', $dom->documentElement);

		$this->assertSame($dom->documentElement, $exception->getNode());
	}
}