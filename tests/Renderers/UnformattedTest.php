<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderers\Unformatted
*/
class UnformattedTest extends Test
{
	/**
	* @testdox Returns unformatted version of rich text
	*/
	public function testRichText()
	{
		$this->configurator->rendering->engine = 'Unformatted';
		$renderer = $this->configurator->getRenderer();

		$this->assertSame(
			'[b]bold[/b]',
			$renderer->render("<r><B><s>[b]</s>bold<e>[/b]</e></B></r>")
		);
	}

	/**
	* @testdox Converts newlines to <br>
	*/
	public function testNl2brHTML()
	{
		$this->configurator->rendering->engine = 'Unformatted';
		$renderer = $this->configurator->getRenderer();

		$this->assertSame(
			"a<br>\nb",
			$renderer->render("<r>a\nb</r>")
		);
	}

	/**
	* @testdox Keeps HTML's special characters escaped
	*/
	public function testSpecialChars()
	{
		$this->configurator->rendering->engine = 'Unformatted';
		$renderer = $this->configurator->getRenderer();

		$this->assertSame(
			'AT&amp;T &lt;b&gt;',
			$renderer->render("<r>AT&amp;T &lt;b&gt;</r>")
		);
	}

	/**
	* @testdox setParameter() doesn't do anything
	*/
	public function testSetParameter()
	{
		$this->configurator->rendering->engine = 'Unformatted';
		$this->configurator->getRenderer()->setParameter('foo', 'bar');
	}
}