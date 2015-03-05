<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection
*/
class MediaSiteCollectionTest extends Test
{
	/**
	* @testdox Extends ArrayObject
	*/
	public function testExtendsArrayObject()
	{
		$this->assertInstanceOf('ArrayObject', new MediaSiteCollection);
	}

	/**
	* @testdox Implements ConfigProvider
	*/
	public function testImplementsConfigProvider()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\ConfigProvider',
			new MediaSiteCollection
		);
	}

	/**
	* @testdox asConfig() returns a Dictionary
	*/
	public function testAsConfigDictionary()
	{
		$collection = new MediaSiteCollection;
		$config     = $collection->asConfig();

		$this->assertInstanceOf('s9e\\TextFormatter\\Configurator\\JavaScript\\Dictionary', $config);
	}

	/**
	* @testdox The config contains a map of ['host.tld' => 'site id']
	*/
	public function testAsConfigHost()
	{
		$collection = new MediaSiteCollection;
		$collection['foo'] = ['host' => 'foo.tld'];

		$config = $collection->asConfig();

		$this->assertEquals(
			['foo.tld' => 'foo'],
			(array) $config
		);
	}

	/**
	* @testdox The config contains a map of ['scheme:' => 'site id']
	*/
	public function testAsConfigScheme()
	{
		$collection = new MediaSiteCollection;
		$collection['foo'] = ['scheme' => 'bar'];

		$config = $collection->asConfig();

		$this->assertEquals(
			['bar:' => 'foo'],
			(array) $config
		);
	}

	/**
	* @testdox Accept "host" as a string or an array of strings
	*/
	public function testAsConfigHosts()
	{
		$collection = new MediaSiteCollection;
		$collection['foo'] = ['host' => 'foo.tld'];
		$collection['bar'] = ['host' => ['bar.tld', 'bar.example']];

		$config = $collection->asConfig();

		$this->assertEquals(
			['foo.tld' => 'foo', 'bar.tld' => 'bar', 'bar.example' => 'bar'],
			(array) $config
		);
	}
}