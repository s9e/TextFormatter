<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\LiveSiteDefinitionProvider;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\LiveSiteDefinitionProvider
*/
class LiveSiteDefinitionProviderTest extends Test
{
	/**
	* @testdox getIds() returns a list of siteIds
	*/
	public function testGetIds()
	{
		$provider = new LiveSiteDefinitionProvider;
		$siteIds  = $provider->getIds();
		$this->assertInternalType('array', $siteIds);
		$this->assertContains('youtube', $siteIds);
	}

	/**
	* @testdox get('youtube') returns a configuration
	*/
	public function testGet()
	{
		$provider   = new LiveSiteDefinitionProvider;
		$siteConfig = $provider->get('youtube');
		$this->assertInternalType('array', $siteConfig);
		$this->assertArrayHasKey('host', $siteConfig);
		$this->assertContains('youtube.com', $siteConfig['host']);
	}

	/**
	* @testdox get('invalid') throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown media site
	*/
	public function testGetInvalid()
	{
		$provider   = new LiveSiteDefinitionProvider;
		$siteConfig = $provider->get('invalid');
	}

	/**
	* @testdox The constructor throws an exception if the dir does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site directory
	*/
	public function testPathInvalid()
	{
		new LiveSiteDefinitionProvider('/invalid/path');
	}
}