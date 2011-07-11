<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/Test.php';

/**
* @covers s9e\TextFormatter\Renderer
*/
class RendererTest extends Test
{
	/**
	* @test
	*/
	public function renderMulti_can_render_multiple_messages_at_once()
	{
		$this->cb->BBCodes->addBBCode('B');
		$this->cb->setTagTemplate('B', '<b><xsl:apply-templates/></b>');

		$this->cb->BBCodes->addBBCode('I');
		$this->cb->setTagTemplate('I', '<i><xsl:apply-templates/></i>');

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

		$parsed = array_map(array($this->parser, 'parse'), $texts);
		$actual = $this->renderer->renderMulti($parsed);

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