<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\PluginParser
*/
class PluginParserTest extends Test
{
	/**
	* @test
	*/
	public function setUp_is_called_once_after_initialization()
	{
		include_once __DIR__ . '/../../src/TextFormatter/PluginParser.php';
		include_once __DIR__ . '/includes/CannedParser.php';

		$parser = new CannedParser($this->parser, array());
		$this->assertSame(1, $parser->_calledCount);
	}
}