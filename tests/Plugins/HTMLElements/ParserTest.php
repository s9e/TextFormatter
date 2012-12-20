<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLElements;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLElements\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLElements\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'x <b>bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
		);
	}
}