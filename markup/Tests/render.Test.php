<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';

class testRender extends \PHPUnit_Framework_TestCase
{
	public function testRenderMulti()
	{
		$cb = new config_builder;

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