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
		$renderer = $this->configurator->getRenderer('Unformatted');

		$this->assertSame(
			'[b]bold[/b]',
			$renderer->render("<r><B><s>[b]</s>bold<e>[/b]</e></B>")
		);
	}

	/**
	* @testdox Converts newlines to <br>
	*/
	public function testNl2brHTML()
	{
		$renderer = $this->configurator->getRenderer('Unformatted');

		$this->assertSame(
			"a<br>\nb",
			$renderer->render("<r>a\nb</r>")
		);
	}

	/**
	* @testdox Converts newlines to <br/> if output is set to xml
	*/
	public function testNl2brXHTML()
	{
		$this->configurator->stylesheet->setOutputMethod('xml');
		$renderer = $this->configurator->getRenderer('Unformatted');

		$this->assertSame(
			"a<br/>\nb",
			$renderer->render("<r>a\nb</r>")
		);
	}

	/**
	* @testdox Keeps HTML's special characters escaped
	*/
	public function testSpecialChars()
	{
		$renderer = $this->configurator->getRenderer('Unformatted');

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
		$this->configurator->getRenderer('Unformatted')->setParameter('foo', 'bar');
	}
}