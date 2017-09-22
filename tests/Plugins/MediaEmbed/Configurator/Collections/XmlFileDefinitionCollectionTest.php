<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\XmlFileDefinitionCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\SiteDefinitionCollection
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\XmlFileDefinitionCollection
*/
class XmlFileDefinitionCollectionTest extends Test
{
	protected function generateDefinition()
	{
		$xml = "<site>
					<host>localhost</host>
					<host>127.0.0.1</host>
					<extract>!localhost/v/(?'id'\\d+)</extract>
					<attributes>
						<playlist defaultValue='12x4' required='true'/>
						<volume defaultValue='11' required='false'/>
					</attributes>
					<iframe width='560' height='315' src='//localhost/e/{@id}'>
						<onload><![CDATA[alert(1)]]></onload>
					</iframe>
				</site>";
		$siteId   = uniqid('mediaembed');
		$filepath = sys_get_temp_dir() . '/' . $siteId . '.xml';
		self::$tmpFiles[] = $filepath;
		file_put_contents($filepath, $xml);

		return $siteId;
	}

	/**
	* @testdox isset() returns TRUE if the site config exists
	*/
	public function testIssetTrue()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$this->assertTrue(isset($collection[$siteId]));
	}

	/**
	* @testdox isset('unknown') returns FALSE
	*/
	public function testIssetFalse()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$this->assertFalse(isset($collection['unknown']));
	}

	/**
	* @testdox isset('*invalid*') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testIssetInvalid()
	{
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		isset($collection['*invalid*']);
	}

	/**
	* @testdox Is iterable
	*/
	public function testIsIterable()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$sites      = iterator_to_array($collection);
		$this->assertInternalType('array', $sites);
		$this->assertArrayHasKey($siteId, $sites);
	}

	/**
	* @testdox get('foo') returns a configuration if foo.xml exists
	*/
	public function testGet()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertInternalType('array', $siteConfig);
		$this->assertArrayHasKey('host', $siteConfig);
		$this->assertContains('localhost', $siteConfig['host']);
	}

	/**
	* @testdox get('unknown') throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Media site 'unknown' does not exist
	*/
	public function testGetUnknown()
	{
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get('unknown');
	}

	/**
	* @testdox get('*invalid*') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testGetInvalid()
	{
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get('*invalid*');
	}

	/**
	* @testdox The constructor throws an exception if the dir does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site directory
	*/
	public function testPathInvalid()
	{
		new XmlFileDefinitionCollection('/invalid/path');
	}

	/**
	* @testdox get() correctly interprets multiple nodes of the same name in XML
	*/
	public function testGetMultipleNodes()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertInternalType('array', $siteConfig);
		$this->assertArrayHasKey('host', $siteConfig);
		$this->assertContains('localhost', $siteConfig['host']);
		$this->assertContains('127.0.0.1', $siteConfig['host']);
	}

	/**
	* @testdox Properly reads child elements with no whitespace
	*/
	public function testNoWhitespace()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertArrayHasKey('attributes', $siteConfig);
		$this->assertInternalType('array',     $siteConfig['attributes']);
		$this->assertArrayHasKey('volume',     $siteConfig['attributes']);
	}

	/**
	* @testdox Properly reads CDATA
	*/
	public function testCDATA()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertArrayHasKey('onload',  $siteConfig['iframe']);
		$this->assertInternalType('string', $siteConfig['iframe']['onload']);
		$this->assertSame('alert(1)',       $siteConfig['iframe']['onload']);
	}

	/**
	* @testdox Iframe dimensions are cast to integer
	*/
	public function testDimensionsAreCastToInteger()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertSame(315, $siteConfig['iframe']['height']);
		$this->assertSame(560, $siteConfig['iframe']['width']);
	}

	/**
	* @testdox Default attribute values are cast to integer if they are made of digits
	*/
	public function testDefaultValueCastToInteger()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertSame(11, $siteConfig['attributes']['volume']['defaultValue']);
	}

	/**
	* @testdox Other default attribute values are left as strings
	*/
	public function testDefaultValueCastToString()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertSame('12x4', $siteConfig['attributes']['playlist']['defaultValue']);
	}

	/**
	* @testdox Attributes' "required" property is cast to bool
	*/
	public function testRequiredCastToBool()
	{
		$siteId     = $this->generateDefinition();
		$collection = new XmlFileDefinitionCollection(sys_get_temp_dir());
		$siteConfig = $collection->get($siteId);
		$this->assertTrue($siteConfig['attributes']['playlist']['required']);
		$this->assertFalse($siteConfig['attributes']['volume']['required']);
	}
}