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
	* @testdox has('youtube') returns TRUE
	*/
	public function testHas()
	{
		$provider = new CachedSiteDefinitionProvider;
		$this->assertTrue($provider->has('youtube'));
	}

	/**
	* @testdox has('unknown') returns FALSE
	*/
	public function testHasFalse()
	{
		$provider = new CachedSiteDefinitionProvider;
		$this->assertFalse($provider->has('unknown'));
	}

	/**
	* @testdox has('*invalid*') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testHasInvalid()
	{
		$provider = new CachedSiteDefinitionProvider;
		$provider->has('*invalid*');
	}

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
	* @testdox get('unknown') returns FALSE
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown media site
	*/
	public function testGetUnknown()
	{
		$provider   = new CachedSiteDefinitionProvider;
		$siteConfig = $provider->get('unknown');
	}

	/**
	* @testdox get('*invalid*') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testGetInvalid()
	{
		$provider   = new CachedSiteDefinitionProvider;
		$siteConfig = $provider->get('*invalid*');
	}
}