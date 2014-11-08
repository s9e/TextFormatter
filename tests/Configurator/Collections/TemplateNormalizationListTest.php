<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;
use s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveComments;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList
*/
class TemplateNormalizationListTest extends Test
{
	/**
	* @testdox append() normalizes a callback into an instance of s9e\TextFormatter\Configurator\TemplateNormalizations\Custom
	*/
	public function testAppendCallback()
	{
		$callback   = function () {};
		$collection = new TemplateNormalizationList;

		$this->assertEquals(
			new Custom($callback),
			$collection->append($callback)
		);
	}

	/**
	* @testdox append() normalizes a string into an instance of a class of the same name in s9e\TextFormatter\Configurator\TemplateNormalizations
	*/
	public function testAppendString()
	{
		$collection = new TemplateNormalizationList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\RemoveComments',
			$collection->append('RemoveComments')
		);
	}

	/**
	* @testdox append() adds instances of s9e\TextFormatter\Configurator\TemplateNormalization as-is
	*/
	public function testAppendInstance()
	{
		$collection = new TemplateNormalizationList;
		$instance   = new RemoveComments;

		$this->assertSame(
			$instance,
			$collection->append($instance)
		);
	}
}