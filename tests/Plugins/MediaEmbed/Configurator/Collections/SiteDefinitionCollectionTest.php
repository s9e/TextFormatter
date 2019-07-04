<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\Collections;

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
	*/
	public function testUnknown()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Media site 'foo' does not exist");

		$collection = new SiteDefinitionCollection;
		$collection->get('foo');
	}

	/**
	* @testdox Throws an exception if the site ID is not valid
	*/
	public function testInvalidID()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid site ID');

		$collection = new SiteDefinitionCollection;
		$collection->set('*x*', []);
	}

	/**
	* @testdox set() throws an exception if the site config is not an array
	*/
	public function testInvalidType()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid site definition type');

		$collection = new SiteDefinitionCollection;
		$collection->set('x', '<site/>');
	}

	/**
	* @testdox set() throws an exception if the site config does not contain a host
	*/
	public function testMissingHost()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Missing host from site definition');

		$collection = new SiteDefinitionCollection;
		$collection->set('x', []);
	}

	/**
	* @testdox add() throws a meaningful exception if the site ID already exists
	*/
	public function testAlreadyExists()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Media site 'foo' already exists");

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
	* @testdox Converts the filterChain field to an array if it's a string
	*/
	public function testFilterChainString()
	{
		$original = [
			'attributes' => ['id' => ['filterChain' => '#int']],
			'host'       => ['localhost'],
			'extract'    => ['/foo/'],
			'scrape'     => []
		];
		$expected = $original;
		$expected['attributes']['id']['filterChain'] = ['#int'];

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