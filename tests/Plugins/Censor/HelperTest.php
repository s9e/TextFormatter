<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Plugins\Censor\Helper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Helper
*/
class HelperTest extends Test
{
	/**
	* @testdox censorHtml() censors text nodes in HTML
	*/
	public function testCensorHtml()
	{
		$this->configurator->Censor->add('foo');

		$this->assertSame(
			'**** bar baz <b>****</b>',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz <b>foo</b>')
		);
	}

	/**
	* @testdox censorHtml() uses custom replacements
	*/
	public function testCensorHtmlCustom()
	{
		$this->configurator->Censor->add('foo', 'bar');

		$this->assertSame(
			'bar bar baz <b>bar</b>',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz <b>foo</b>')
		);
	}

	/**
	* @testdox censorHtml() escapes custom replacements
	*/
	public function testCensorHtmlEscape()
	{
		$this->configurator->Censor->add('bar', '<...>') ;

		$this->assertSame(
			'foo &lt;...&gt; baz',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML tag names
	*/
	public function testCensorHtmlTagNames()
	{
		$this->configurator->Censor->add('span') ;

		$this->assertSame(
			'<span>****</span>',
			$this->configurator->Censor->getHelper()->censorHtml('<span>span</span>')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML attribute names
	*/
	public function testCensorHtmlAttributeNames()
	{
		$this->configurator->Censor->add('title') ;

		$this->assertSame(
			'<span title="">****</span>',
			$this->configurator->Censor->getHelper()->censorHtml('<span title="">title</span>')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML attribute values
	*/
	public function testCensorHtmlAttributeValues()
	{
		$this->configurator->Censor->add('title') ;

		$this->assertSame(
			'<span title="title">****</span>',
			$this->configurator->Censor->getHelper()->censorHtml('<span title="title">title</span>')
		);
	}

	/**
	* @testdox censorText() censors plain text
	*/
	public function testCensorText()
	{
		$this->configurator->Censor->add('foo') ;

		$this->assertSame(
			'**** bar baz',
			$this->configurator->Censor->getHelper()->censorText('foo bar baz')
		);
	}

	/**
	* @testdox censorText() uses custom replacements
	*/
	public function testCensorTextCustom()
	{
		$this->configurator->Censor->add('foo', 'bar');

		$this->assertSame(
			'bar bar baz',
			$this->configurator->Censor->getHelper()->censorText('foo bar baz')
		);
	}

	/**
	* @testdox reparse() preserves old tags
	*/
	public function testReparseOld()
	{
		$this->configurator->Censor->add('bar');

		$xml = '<r>foo <CENSOR>bar</CENSOR> baz</r>';

		$this->assertSame(
			$xml,
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() updates replacement in old tags
	*/
	public function testReparseOldReplacement()
	{
		$this->configurator->Censor->add('bar', 'baz');

		$xml = '<r>foo <CENSOR>bar</CENSOR> <CENSOR with="****">bar</CENSOR> baz</r>';

		$this->assertSame(
			'<r>foo <CENSOR with="baz">bar</CENSOR> <CENSOR with="baz">bar</CENSOR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() add new tags
	*/
	public function testReparseNew()
	{
		$this->configurator->Censor->add('bar');

		$xml = '<r>foo bar baz</r>';

		$this->assertSame(
			'<r>foo <CENSOR>bar</CENSOR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() uses replacements in new tags
	*/
	public function testReparseReplacement()
	{
		$this->configurator->Censor->add('bar', 'quux');

		$xml = '<r>foo bar baz</r>';

		$this->assertSame(
			'<r>foo <CENSOR with="quux">bar</CENSOR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() escapes replacements
	*/
	public function testReparseReplacementEscape()
	{
		$this->configurator->Censor->add('bar', '<"BAR">');

		$xml = '<r>foo bar baz</r>';

		$this->assertSame(
			'<r>foo <CENSOR with="&lt;&quot;BAR&quot;&gt;">bar</CENSOR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() replace the "pt" root node with "rt" if a new match is found
	*/
	public function testReparseNewRoot()
	{
		$this->configurator->Censor->add('bar');

		$xml = '<t>foo bar baz</t>';

		$this->assertSame(
			'<r>foo <CENSOR>bar</CENSOR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() does not replace tag names
	*/
	public function testReparseTagNames()
	{
		$this->configurator->Censor->add('BAR');

		$xml = '<r>foo <BAR>bar</BAR> baz</r>';

		$this->assertSame(
			'<r>foo <BAR><CENSOR>bar</CENSOR></BAR> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() does not replace attribute names
	*/
	public function testReparseAttributeNames()
	{
		$this->configurator->Censor->add('bar');

		$xml = '<r>foo <FOO bar="">bar</FOO> baz</r>';

		$this->assertSame(
			'<r>foo <FOO bar=""><CENSOR>bar</CENSOR></FOO> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() does not replace attribute values
	*/
	public function testReparseAttributeValues()
	{
		$this->configurator->Censor->add('bar');

		$xml = '<r>foo <FOO bar="bar">bar</FOO> baz</r>';

		$this->assertSame(
			'<r>foo <FOO bar="bar"><CENSOR>bar</CENSOR></FOO> baz</r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}

	/**
	* @testdox reparse() does not erroneously alter tags whose name resembles Censor's tag name
	*/
	public function testReparseNoCollision()
	{
		$this->configurator->plugins->load('Censor', ['tagName' => 'C']);

		$xml = '<r>foo <C>bar</C> <CC>bar</CC> </r>';

		$this->assertSame(
			'<r>foo bar <CC>bar</CC> </r>',
			$this->configurator->Censor->getHelper()->reparse($xml)
		);
	}
}