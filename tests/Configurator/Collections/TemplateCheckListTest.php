<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TemplateCheckList;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowCopy;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TemplateCheckList
*/
class TemplateCheckListTest extends Test
{
	/**
	* @testdox append() normalizes a string into an instance of a class of the same name in s9e\TextFormatter\Configurator\TemplateChecks
	*/
	public function testAppendNormalizeValue()
	{
		$collection = new TemplateCheckList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\TemplateChecks\\DisallowCopy',
			$collection->append('DisallowCopy')
		);
	}

	/**
	* @testdox append() adds instances of s9e\TextFormatter\Configurator\TemplateCheck as-is
	*/
	public function testAppendInstance()
	{
		$collection = new TemplateCheckList;
		$check      = new DisallowCopy;

		$this->assertSame(
			$check,
			$collection->append($check)
		);
	}
}