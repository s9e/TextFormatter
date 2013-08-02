<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\BundleGenerator
*/
class BundleGeneratorTest extends Test
{
	/**
	* @testdox generate() returns the bundle's PHP source
	*/
	public function testGenerate()
	{
		$this->assertContains(
			'class MyBundle',
			$this->configurator->bundleGenerator->generate('MyBundle')
		);
	}

	/**
	* @testdox generate() accepts namespaced class names
	*/
	public function testGenerateNamespace()
	{
		$php = $this->configurator->bundleGenerator->generate('My\\Bundle');

		$this->assertContains('namespace My;', $php);
		$this->assertContains('class Bundle', $php);
	}

	/**
	* @testdox A custom serializer can be set in $bundleGenerator->serializer
	*/
	public function testCustomSerializer()
	{
		$mock = $this->getMock('stdClass', ['serialize']);
		$mock->expects($this->any())
		     ->method('serialize')
		     ->will($this->returnValue('O:8:"stdClass":1:{s:6:"foobar";i:1;}'));

		$this->configurator->bundleGenerator->serializer = [$mock, 'serialize'];
		$php = $this->configurator->bundleGenerator->generate('MyBundle');

		$this->assertContains('foobar', $php);
	}

	/**
	* @testdox A custom unserializer can be set in $bundleGenerator->unserializer
	*/
	public function testCustomUnserializer()
	{
		$this->configurator->bundleGenerator->unserializer = 'myunserializer';
		$php = $this->configurator->bundleGenerator->generate('MyBundle');

		$this->assertContains('myunserializer', $php);
	}
}