<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\CachedSiteDefinitionProvider;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteDefinitionProvider
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\CachedSiteDefinitionProvider
*/
class CachedSiteDefinitionProviderTest extends Test
{
	/**
	* @testdox getIds() returns a list of siteIds
	*/
	public function testGetIds()
	{
		$provider = new CachedSiteDefinitionProvider;
		$siteIds  = $provider->getIds();
		$this->assertInternalType('array', $siteIds);
		$this->assertContains('youtube', $siteIds);
	}

	/**
	* @testdox get('youtube') returns a configuration
	*/
	public function testGet()
	{
		$provider   = new CachedSiteDefinitionProvider;
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
		$provider   = new CachedSiteDefinitionProvider;
		$siteConfig = $provider->get('invalid');
	}
}