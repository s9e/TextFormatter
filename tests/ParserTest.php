<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox
	*/
	public function test()
	{
		$configurator = new Configurator;
		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('I');

		$configurator->tags['I']->rules->autoReopen();

		$parser = $configurator->getParser();

		$this->assertSame(
			'<rt><B><st>[b]</st>x<I><st>[i]</st>y</I><et>[/b]</et></B><I>z<et>[/i]</et></I></rt>',
			$parser->parse('[b]x[i]y[/b]z[/i]')
		);
	}

	/**
	* @testdox getLogger() returns an instance of Logger
	*/
	public function testGetLogger()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Parser\\Logger',
			$this->configurator->getParser()->getLogger()
		);
	}
}