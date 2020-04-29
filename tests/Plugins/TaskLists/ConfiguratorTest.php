<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\TaskLists;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\IdentifierFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\TaskLists\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "TASK" tag with an "id" attribute and a "state" attribute
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('TaskLists');
		$this->assertTrue($this->configurator->tags->exists('TASK'));

		$tag = $this->configurator->tags->get('TASK');

		$this->assertTrue($tag->attributes->exists('id'));
		$this->assertTrue($tag->attributes['id']->filterChain->contains(new IdentifierFilter));

		$this->assertTrue($tag->attributes->exists('state'));
		$this->assertTrue($tag->attributes['state']->filterChain->contains(new IdentifierFilter));
	}

	/**
	* @testdox Returns no config
	*/
	public function testAsConfig()
	{
		$this->assertNull($this->configurator->TaskLists->asConfig());
	}
}