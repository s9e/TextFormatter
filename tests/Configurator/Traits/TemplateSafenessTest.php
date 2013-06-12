<?php

namespace s9e\TextFormatter\Tests\Configurator\Traits;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

/**
* @covers s9e\TextFormatter\Configurator\Traits\TemplateSafeness
*/
class TemplateSafenessTest extends Test
{
	/**
	* @testdox markAsSafeAsURL() is chainable
	*/
	public function testMarkAsSafeAsURLChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeAsURL());
	}

	/**
	* @testdox markAsSafeInCSS() is chainable
	*/
	public function testMarkAsSafeInCSSChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeInCSS());
	}

	/**
	* @testdox markAsSafeInJS() is chainable
	*/
	public function testMarkAsSafeInJSChainable()
	{
		$item = new DummyItem;
		$this->assertSame($item, $item->markAsSafeInJS());
	}
}

class DummyItem
{
	use TemplateSafeness;
}