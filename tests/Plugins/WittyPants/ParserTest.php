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
	* @testdox 
	*/
	public function test()
	{
		$this->configurator->plugins->load('WittyPants');
		$parser = $this->configurator->getParser();

		$this->assertSame(
			'<WP char="‘">\'</WP>Good morning, Frank,<WP char="’">\'</WP> greeted HAL.',
			$parser->parse("'Good morning, Frank,' greeted HAL.")
		);
	}
}