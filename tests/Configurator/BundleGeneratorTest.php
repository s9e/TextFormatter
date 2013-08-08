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

	/**
	* @testdox generate('Foo', ['finalizeParser' => $callback]) calls $callback and passes it an instance of Parser
	*/
	public function testParserCallback()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($this->isInstanceOf('s9e\\TextFormatter\\Parser'));

		$this->configurator->bundleGenerator->generate('Foo', ['finalizeParser' => [$mock, 'foo']]);
	}

	/**
	* @testdox Modification made to the parser via callback appear in the generated bundle
	*/
	public function testParserCallbackPersist()
	{
		$this->configurator->Autolink;

		$bundle = $this->configurator->bundleGenerator->generate(
			'Foo',
			[
				'finalizeParser' => function ($parser)
				{
					$parser->disablePlugin('Autolink');
					$parser->disableTag('URL');
				}
			]
		);

		$this->assertRegexp(
			'/\\\\"Autolink\\\\";[^}]*s:10:\\\\"isDisabled\\\\";b:1;/',
			$bundle
		);
		$this->assertRegexp(
			'/\\\\"URL\\\\";[^}]*s:10:\\\\"isDisabled\\\\";b:1;/',
			$bundle
		);
	}

	/**
	* @testdox generate('Foo', ['finalizeRenderer' => $callback]) calls $callback and passes it an instance of Renderer
	*/
	public function testRendererCallback()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($this->isInstanceOf('s9e\\TextFormatter\\Renderer'));

		$this->configurator->bundleGenerator->generate('Foo', ['finalizeRenderer' => [$mock, 'foo']]);
	}

	/**
	* @testdox Modification made to the renderer via callback appear in the generated bundle
	*/
	public function testRendererCallbackPersist()
	{
		$bundle = $this->configurator->bundleGenerator->generate(
			'Foo',
			[
				'finalizeRenderer' => function ($renderer)
				{
					$renderer->foo = 'bar';
				}
			]
		);

		$this->assertContains(
			's:3:\\"foo\\";s:3:\\"bar\\";',
			$bundle
		);
	}

	/**
	* @testdox If the renderer is an instance of the PHP renderer, its source is automatically loaded if the path to its file is known
	*/
	public function testAutoInclude()
	{
		$cacheDir = sys_get_temp_dir();
		$rendererGenerator = $this->configurator->setRendererGenerator('PHP', $cacheDir);

		$bundle    = $this->configurator->bundleGenerator->generate('Foo');
		$className = $rendererGenerator->lastClassName;
		$filepath  = $rendererGenerator->lastFilepath;

		unlink($filepath);

		$expected = "
		if (!class_exists(" . var_export($className, true) . ", false)
		 && file_exists(" . var_export($filepath, true) . "))
		{
			include " . var_export($filepath, true) . ";
		}";

		$this->assertContains($expected, $bundle);
	}

	/**
	* @testdox Does not attempt to load the renderer's source if autoInclude is false
	*/
	public function testAutoIncludeFalse()
	{
		$cacheDir = sys_get_temp_dir();
		$rendererGenerator = $this->configurator->setRendererGenerator('PHP', $cacheDir);

		$bundle    = $this->configurator->bundleGenerator->generate('Foo', ['autoInclude' => false]);
		$className = $rendererGenerator->lastClassName;
		$filepath  = $rendererGenerator->lastFilepath;

		unlink($filepath);

		$expected = "
		if (!class_exists(" . var_export($className, true) . ", false)
		 && file_exists(" . var_export($filepath, true) . "))
		{
			include " . var_export($filepath, true) . ";
		}";

		$this->assertNotContains($expected, $bundle);
	}
}