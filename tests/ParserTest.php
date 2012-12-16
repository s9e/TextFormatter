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

		$parser = $configurator->getParser();

		var_dump($parser->parse('[b]zz[/b]'));
	}
}