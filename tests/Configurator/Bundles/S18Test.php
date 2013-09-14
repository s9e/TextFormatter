<?php

namespace s9e\TextFormatter\Tests\Configurator\Bundles;

use s9e\TextFormatter\Configurator\Bundles\S18;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Bundles\S18
*/
class S18Test extends Test
{
	/**
	* @testdox Features
	*/
	public function testFeatures()
	{
		$configurator = S18::getConfigurator();

		$this->assertTrue(isset($configurator->Autoemail));
		$this->assertTrue(isset($configurator->Autolink));

		$this->assertTrue($configurator->BBCodes->exists('b'));
		$this->assertTrue($configurator->BBCodes->exists('bdo'));
		$this->assertTrue($configurator->BBCodes->exists('black'));
		$this->assertTrue($configurator->BBCodes->exists('blue'));
		$this->assertTrue($configurator->BBCodes->exists('br'));
		$this->assertTrue($configurator->BBCodes->exists('center'));
		$this->assertTrue($configurator->BBCodes->exists('code'));
		$this->assertTrue($configurator->BBCodes->exists('color'));
		$this->assertTrue($configurator->BBCodes->exists('email'));
		$this->assertTrue($configurator->BBCodes->exists('flash'));
		$this->assertTrue($configurator->BBCodes->exists('font'));
		$this->assertTrue($configurator->BBCodes->exists('ftp'));
		$this->assertTrue($configurator->BBCodes->exists('glow'));
		$this->assertTrue($configurator->BBCodes->exists('green'));
		$this->assertTrue($configurator->BBCodes->exists('hr'));
		$this->assertTrue($configurator->BBCodes->exists('html'));
		$this->assertTrue($configurator->BBCodes->exists('i'));
		$this->assertTrue($configurator->BBCodes->exists('img'));
		$this->assertTrue($configurator->BBCodes->exists('iurl'));
		$this->assertTrue($configurator->BBCodes->exists('left'));
		$this->assertTrue($configurator->BBCodes->exists('li'));
		$this->assertTrue($configurator->BBCodes->exists('list'));
		$this->assertTrue($configurator->BBCodes->exists('ltr'));
		$this->assertTrue($configurator->BBCodes->exists('me'));
		$this->assertTrue($configurator->BBCodes->exists('move'));
		$this->assertTrue($configurator->BBCodes->exists('nobbc'));
		$this->assertTrue($configurator->BBCodes->exists('php'));
		$this->assertTrue($configurator->BBCodes->exists('pre'));
		$this->assertTrue($configurator->BBCodes->exists('quote'));
		$this->assertTrue($configurator->BBCodes->exists('red'));
		$this->assertTrue($configurator->BBCodes->exists('right'));
		$this->assertTrue($configurator->BBCodes->exists('rtl'));
		$this->assertTrue($configurator->BBCodes->exists('s'));
		$this->assertTrue($configurator->BBCodes->exists('shadow'));
		$this->assertTrue($configurator->BBCodes->exists('size'));
		$this->assertTrue($configurator->BBCodes->exists('sub'));
		$this->assertTrue($configurator->BBCodes->exists('sup'));
		$this->assertTrue($configurator->BBCodes->exists('table'));
		$this->assertTrue($configurator->BBCodes->exists('td'));
		$this->assertTrue($configurator->BBCodes->exists('time'));
		$this->assertTrue($configurator->BBCodes->exists('tr'));
		$this->assertTrue($configurator->BBCodes->exists('tt'));
		$this->assertTrue($configurator->BBCodes->exists('u'));
		$this->assertTrue($configurator->BBCodes->exists('url'));
		$this->assertTrue($configurator->BBCodes->exists('white'));
	}
}