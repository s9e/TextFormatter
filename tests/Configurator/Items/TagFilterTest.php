<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\TagFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\TagFilter
*/
class TagFilterTest extends Test
{
	/**
	* @testdox Sets the filter's signature to ['tag' => null]
	*/
	public function testDefaultSignature()
	{
		$filter = new TagFilter(function($v){});
		$config = $filter->asConfig();

		$this->assertSame(
			['tag' => null],
			$config['params']
		);
	}
}