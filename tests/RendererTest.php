<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderer
*/
class RendererTest extends Test
{
	protected $renderer;

	public function setUp()
	{
		$tags = new TagCollection;
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$stylesheet = new Stylesheet($tags);
		$this->renderer = new Renderer($stylesheet->get());
	}

	/**
	* @testdox Renders plain text
	*/
	public function testPlainText()
	{
		$xml = '<pt>Plain text</pt>';

		$this->assertSame(
			'Plain text',
			$this->renderer->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in HTML
	*/
	public function testMultiLineTextHTML()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('html');
		$renderer = new Renderer($stylesheet->get());

		$xml = '<pt>One<br/>two</pt>';

		$this->assertSame(
			'One<br>two',
			$renderer->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in XHTML
	*/
	public function testMultiLineTextXHTML()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('xml');
		$renderer = new Renderer($stylesheet->get());

		$xml = '<pt>One<br/>two</pt>';

		$this->assertSame(
			'One<br/>two',
			$renderer->render($xml)
		);
	}

	/**
	* @testdox Renders rich text
	*/
	public function testRichText()
	{
		$xml = '<rt>Hello <B><st>[b]</st>world<et>[/b]</et></B>!</rt>';

		$this->assertSame(
			'Hello <b>world</b>!',
			$this->renderer->render($xml)
		);
	}

	/**
	* @testdox renderMulti() renders multiple messages at once
	*/
	public function testMulti()
	{
		$parsed = array(
			'<rt>Hello <B><st>[b]</st>world<et>[/b]</et></B>!</rt>',
			'<pt>Plain text</pt>',
			'<rt>Hello <B><st>[b]</st>world<et>[/b]</et></B>!</rt>',
			'<pt>Plain text</pt>'
		);

		$expected = array(
			'Hello <b>world</b>!',
			'Plain text',
			'Hello <b>world</b>!',
			'Plain text'
		);

		$this->assertSame(
			$expected,
			$this->renderer->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() preserves keys and order
	*/
	public function testMultiOrder()
	{
		$parsed = array(
			'<rt><B>One</B></rt>',
			'<pt>Two</pt>',
			'p3' => '<rt><B>Three</B></rt>',
			'p4' => '<pt>Four</pt>',
			'<rt><B>Five</B></rt>',
			'<pt>Six</pt>'
		);

		$expected = array(
			'<b>One</b>',
			'Two',
			'p3' => '<b>Three</b>',
			'p4' => 'Four',
			'<b>Five</b>',
			'Six'
		);

		$this->assertSame(
			$expected,
			$this->renderer->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() renders multi-line text in HTML
	*/
	public function testMultiMultiPlainTextHTML()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('html');
		$renderer = new Renderer($stylesheet->get());

		$parsed   = array('<pt>One<br/>two</pt>');
		$expected = array('One<br>two');

		$this->assertSame(
			$expected,
			$renderer->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() renders multi-line text in XHTML
	*/
	public function testMultiMultiPlainTextXHTML()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('xml');
		$renderer = new Renderer($stylesheet->get());

		$parsed   = array('<pt>One<br/>two</pt>');
		$expected = array('One<br/>two');

		$this->assertSame(
			$expected,
			$renderer->renderMulti($parsed)
		);
	}

	/**
	* @testdox Renderer is serializable
	*/
	public function testSerializable()
	{
		$this->assertEquals(
			$this->renderer,
			unserialize(serialize($this->renderer))
		);
	}

	/**
	* @testdox setParameter() sets the value of a parameter
	*/
	public function testSetParameter()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/>';
		$configurator->stylesheet->parameters->add('foo');

		$renderer = $configurator->getRenderer();
		$renderer->setParameter('foo', 'bar');

		$this->assertSame(
			'bar',
			$renderer->render('<rt><X/></rt>')
		);
	}

	/**
	* @testdox setParameters() sets the values of any number of parameters in an associative array
	*/
	public function testSetParameters()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/><xsl:value-of select="$bar"/>';
		$configurator->stylesheet->parameters->add('foo');
		$configurator->stylesheet->parameters->add('bar');

		$renderer = $configurator->getRenderer();
		$renderer->setParameters(array(
			'foo' => 'FOO',
			'bar' => 'BAR'
		));

		$this->assertSame(
			'FOOBAR',
			$renderer->render('<rt><X/></rt>')
		);
	}
}