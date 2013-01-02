<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\BBCodes;
use s9e\TextFormatter\Tests\Test;

/**
* @coversNothing
*/
class BBCodesTest extends Test
{
	/**
	* @testdox BBCodes from repository.xml render nicely
	* @dataProvider getPredefinedBBCodesTests
	*/
	public function test($original, $expected)
	{
		$configurator = new Configurator;

		// Capture the names of the BBCodes used
		preg_match_all('/\\[([\\w*]+)/', $original, $matches);

		foreach ($matches[1] as $bbcodeName)
		{
			if (!isset($configurator->BBCodes[$bbcodeName]))
			{
				$configurator->BBCodes->addFromRepository($bbcodeName);
			}
		}

		$configurator->addHTML5Rules();

		$xml  = $configurator->getParser()->parse($original);
		$html = $configurator->getRenderer()->render($xml);

		$this->assertSame(
			$expected,
			$html
		);
	}

	public function getPredefinedBBCodesTests()
	{
		return array(
			array(
				'x [b]bold[/b] y',
				'x <b>bold</b> y'
			),
			array(
				'x [B]BOLD[/b] y',
				'x <b>BOLD</b> y'
			),
			array(
				'x [C][b]not bold[/b][/C] y',
				'x <code class="inline">[b]not bold[/b]</code> y'
			),
			array(
				'x [C:123][C][b]not bold[/b][/C][/C:123] y',
				'x <code class="inline">[C][b]not bold[/b][/C]</code> y'
			),
			array(
				'x [COLOR=red]is red[/COLOR] y',
				'x <span style="color:red">is red</span> y'
			),
			array(
				'x [COLOR=red]is [COLOR=green]green[/COLOR] and red[/COLOR] y',
				'x <span style="color:red">is <span style="color:green">green</span> and red</span> y'
			),
			array(
				'x [EMAIL]test@example.org[/EMAIL] y',
				'x <a href="mailto:test@example.org">test@example.org</a> y'
			),
			array(
				'x [EMAIL=test@example.org]email[/EMAIL] y',
				'x <a href="mailto:test@example.org">email</a> y'
			),
			array(
				'x [i]italic[/I] y',
				'x <i>italic</i> y'
			),
			array(
				'x [B]bold [i]italic[/b][/I] y',
				'x <b>bold <i>italic</i></b><i></i> y'
			),
			array(
				'x [img]http://example.org/foo.png[/img] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
			array(
				'x [img=http://example.org/foo.png] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
			array(
				'x [img=http://example.org/foo.png /] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
		);
	}
}