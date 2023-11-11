<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Document;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\Custom
*/
class CustomTest extends Test
{
	/**
	* @testdox normalize() calls the user-defined callback with a DOMElement as argument
	*/
	public function testNormalize()
	{
		$dom = new Document;
		$dom->loadXML('<x/>');

		$mock = $this->getMockBuilder('stdClass')
		             ->addMethods(['foo'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($dom->documentElement);

		$normalizer = new Custom([$mock, 'foo']);
		$normalizer->normalize($dom->documentElement);
	}
}