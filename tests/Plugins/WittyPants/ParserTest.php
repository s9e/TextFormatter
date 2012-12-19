<?php

namespace s9e\TextFormatter\Tests\Plugins\WittyPants;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\WittyPants\Parser;

/**
* @covers s9e\TextFormatter\Plugins\WittyPants\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parser works
	* @dataProvider getData
	*/
	public function test($original, $expected, $setup = null)
	{
		$this->configurator->plugins->load('WittyPants');

		$parser   = $this->configurator->getParser();
		$renderer = $this->configurator->getRenderer();

		$this->assertSame($expected, $renderer->render($parser->parse($original)));
	}

	public function getData()
	{
		return array(
			array(
				"'Good morning, Frank,' greeted HAL.",
				'‘Good morning, Frank,’ greeted HAL.'
			),
			array(
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'“‘Good morning, Frank,’ greeted HAL.” is how the book starts.'
			)
		);
	}
}