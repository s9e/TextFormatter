<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Plugins\BBCodes\Repository;
use s9e\TextFormatter\Plugins\BBCodes\RepositoryCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\RepositoryCollection
*/
class RepositoryCollectionTest extends Test
{
	/**
	* @testdox Instances of Repository are added as-is
	*/
	public function testAddRepository()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');

		$collection = new RepositoryCollection;
		$collection->add('foo', $repository);

		$this->assertSame($repository, $collection->get('foo'));
	}

	/**
	* @testdox Anything else gets a new instance of Repository to be created
	*/
	public function testAddS()
	{
		$collection = new RepositoryCollection;
		$collection->add('foo', __DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\Repository', 
			$collection->get('foo')
		);
	}
}