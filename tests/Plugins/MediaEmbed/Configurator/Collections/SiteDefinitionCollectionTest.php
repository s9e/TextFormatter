<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\SiteDefinitionCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\SiteDefinitionCollection
*/
class SiteDefinitionCollectionTest extends Test
{
	/**
	* @testdox Can set and retrieve definitions
	*/
	public function testWorks()
	{
		$config = [
			'name'       => 'Foo',
			'attributes' => [],
			'extract'    => [],
			'host'       => ['localhost'],
			'scrape'     => [['extract' => [], 'match' => ['//']]]
		];

		$collection = new SiteDefinitionCollection;
		$collection->set('foo', $config);
		$this->assertEquals($config, $collection->get('foo'));
	}

	/**
	* @testdox get() throws a meaningful exception if the site ID does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Media site 'foo' does not exist
	*/
	public function testUnknown()
	{
		$collection = new SiteDefinitionCollection;
		$collection->get('foo');
	}

	/**
	* @testdox Throws an exception if the site ID is not valid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testInvalidID()
	{
		$collection = new SiteDefinitionCollection;
		$collection->set('*x*', []);
	}

	/**
	* @testdox set() throws an exception if the site config is not an array
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site definition type
	*/
	public function testInvalidType()
	{
		$collection = new SiteDefinitionCollection;
		$collection->set('x', '<site/>');
	}

	/**
	* @testdox set() throws an exception if the site config does not contain a host
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Missing host from site definition
	*/
	public function testMissingHost()
	{
		$collection = new SiteDefinitionCollection;
		$collection->set('x', []);
	}

	/**
	* @testdox add() throws a meaningful exception if the site ID already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Media site 'foo' already exists
	*/
	public function testAlreadyExists()
	{
		$collection = new SiteDefinitionCollection;
		$collection->onDuplicate('error');
		$collection->add('foo', ['host' => ['localhost']]);
		$collection->add('foo', ['host' => ['localhost']]);
	}

	/**
	* @testdox Converts the extract field to an array if it's a string
	*/
	public function testExtractString()
	{
		$original = ['attributes' => [], 'host' => ['localhost'], 'extract' => '/foo/',   'scrape' => []];
		$expected = $original;
		$expected['extract'] = ['/foo/'];

		$collection = new SiteDefinitionCollection;
		$collection->add('foo', $original);

		$this->assertEquals($expected, $collection['foo']);
	}

	/**
	* @testdox Preserves the extract field if it's an array
	*/
	public function testExtractArray()
	{
		$original = ['attributes' => [], 'host' => ['localhost'], 'extract' => ['/foo/'], 'scrape' => []];
		$expected = $original;

		$collection = new SiteDefinitionCollection;
		$collection->add('foo', $original);

		$this->assertEquals($expected, $collection['foo']);
	}

	/**
	* @testdox Normalizes the scrape config
	*/
	public function testNormalizeScrape()
	{
		$original = [
			'attributes' => [],
			'host'       => ['localhost'],
			'extract'    => [],
			'scrape'     => ['extract' => '/foo/']
		];
		$expected = $original;
		$expected['scrape'] = [['extract' => ['/foo/'], 'match' => ['//']]];

		$collection = new SiteDefinitionCollection;
		$collection->add('foo', $original);

		$this->assertEquals($expected, $collection['foo']);
	}
}