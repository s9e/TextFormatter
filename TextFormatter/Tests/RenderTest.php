<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';

class RenderTest extends \PHPUnit_Framework_TestCase
{
	public function testRenderMulti()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b');
		$cb->setBBCodeTemplate('b', '<b><xsl:apply-templates/></b>');

		$cb->addBBCode('i');
		$cb->setBBCodeTemplate('i', '<i><xsl:apply-templates/></i>');

		$texts = array(
			'Some [b]bold[/b] text.',
			'Some [i]italic[/i] text.',
			'Some [b]bold[/b] text.',
			'Some [i]italic[/i] text.',
			'Some [b]bold[/b] text.',
			'Some [i]italic[/i] text.',
			'Some [b]bold[/b] text.',
			'Some [i]italic[/i] text.',
			'Some plain text.'
		);

		$parsed   = array_map(array($cb->getParser(), 'parse'), $texts);
		$actual   = $cb->getRenderer()->renderMulti($parsed);

		$expected = array(
			'Some <b>bold</b> text.',
			'Some <i>italic</i> text.',
			'Some <b>bold</b> text.',
			'Some <i>italic</i> text.',
			'Some <b>bold</b> text.',
			'Some <i>italic</i> text.',
			'Some <b>bold</b> text.',
			'Some <i>italic</i> text.',
			'Some plain text.'
		);

		$this->assertSame($expected, $actual);
	}
}