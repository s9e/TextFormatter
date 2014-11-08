<?php

namespace s9e\TextFormatter\Tests\Configurator\Bundles;

use s9e\TextFormatter\Configurator\Bundles\F5;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Bundles\F5
*/
class F5Test extends Test
{
	/**
	* @testdox Features
	*/
	public function testFeatures()
	{
		$configurator = F5::getConfigurator();

		$this->assertTrue(isset($configurator->Autoemail));
		$this->assertTrue(isset($configurator->Autolink));

		$this->assertTrue($configurator->BBCodes->exists('B'));
		$this->assertTrue($configurator->BBCodes->exists('U'));
		$this->assertTrue($configurator->BBCodes->exists('I'));
		$this->assertTrue($configurator->BBCodes->exists('S'));
		$this->assertTrue($configurator->BBCodes->exists('DEL'));
		$this->assertTrue($configurator->BBCodes->exists('INS'));
		$this->assertTrue($configurator->BBCodes->exists('EM'));
		$this->assertTrue($configurator->BBCodes->exists('COLOR'));
		$this->assertTrue($configurator->BBCodes->exists('COLOUR'));
		$this->assertTrue($configurator->BBCodes->exists('H'));
		$this->assertTrue($configurator->BBCodes->exists('URL'));
		$this->assertTrue($configurator->BBCodes->exists('EMAIL'));
		$this->assertTrue($configurator->BBCodes->exists('TOPIC'));
		$this->assertTrue($configurator->BBCodes->exists('POST'));
		$this->assertTrue($configurator->BBCodes->exists('FORUM'));
		$this->assertTrue($configurator->BBCodes->exists('USER'));
		$this->assertTrue($configurator->BBCodes->exists('IMG'));
		$this->assertTrue($configurator->BBCodes->exists('QUOTE'));
		$this->assertTrue($configurator->BBCodes->exists('CODE'));
		$this->assertTrue($configurator->BBCodes->exists('LIST'));
		$this->assertTrue($configurator->BBCodes->exists('*'));

		$this->assertTrue($configurator->Emoticons->exists(':)'));
		$this->assertTrue($configurator->Emoticons->exists('=)'));
		$this->assertTrue($configurator->Emoticons->exists(':|'));
		$this->assertTrue($configurator->Emoticons->exists('=|'));
		$this->assertTrue($configurator->Emoticons->exists(':('));
		$this->assertTrue($configurator->Emoticons->exists('=('));
		$this->assertTrue($configurator->Emoticons->exists(':D'));
		$this->assertTrue($configurator->Emoticons->exists('=D'));
		$this->assertTrue($configurator->Emoticons->exists(':o'));
		$this->assertTrue($configurator->Emoticons->exists(':O'));
		$this->assertTrue($configurator->Emoticons->exists(';)'));
		$this->assertTrue($configurator->Emoticons->exists(':/'));
		$this->assertTrue($configurator->Emoticons->exists(':P'));
		$this->assertTrue($configurator->Emoticons->exists(':p'));
		$this->assertTrue($configurator->Emoticons->exists(':lol:'));
		$this->assertTrue($configurator->Emoticons->exists(':mad:'));
		$this->assertTrue($configurator->Emoticons->exists(':rolleyes:'));
		$this->assertTrue($configurator->Emoticons->exists(':cool:'));
	}
}