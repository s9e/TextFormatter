<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../src/autoloader.php';

/**
* @covers s9e\TextFormatter\PluginParser
*/
class PluginParserTest extends Test
{
	/**
	* @test
	*/
	public function setUp_is_called_once_after_initialization()
	{
		include_once __DIR__ . '/../src/PluginParser.php';
		include_once __DIR__ . '/includes/CannedParser.php';

		$parser = new CannedParser($this->parser, array());
		$this->assertSame(1, $parser->_calledCount);
	}
}