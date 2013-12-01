<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Tests\Test;

trait RendererTests
{
	/**
	* @testdox Renders plain text
	*/
	public function testPlainText()
	{
		$xml = '<pt>Plain text</pt>';

		$this->assertSame(
			'Plain text',
			$this->configurator->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in HTML
	*/
	public function testMultiLineTextHTML()
	{
		$xml = '<pt>One<br/>two</pt>';

		$this->configurator->stylesheet->setOutputMethod('html');

		$this->assertSame(
			'One<br>two',
			$this->configurator->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in XHTML
	*/
	public function testMultiLineTextXHTML()
	{
		$xml = '<pt>One<br/>two</pt>';

		$this->configurator->stylesheet->setOutputMethod('xml');

		$this->assertSame(
			'One<br/>two',
			$this->configurator->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox Renders rich text
	*/
	public function testRichText()
	{
		$xml = '<rt>Hello <B><s>[b]</s>world<e>[/b]</e></B>!</rt>';

		$this->configurator->tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$this->assertSame(
			'Hello <b>world</b>!',
			$this->configurator->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox renderMulti() renders multiple messages at once
	*/
	public function testMulti()
	{
		$parsed = [
			'<rt>1Hello <B><s>[b]</s>world<e>[/b]</e></B>!</rt>',
			'<pt>2Plain text</pt>',
			'<rt>3Hello <B><s>[b]</s>world<e>[/b]</e></B>!</rt>',
			'<pt>4Plain text</pt>'
		];

		$expected = [
			'1Hello <b>world</b>!',
			'2Plain text',
			'3Hello <b>world</b>!',
			'4Plain text'
		];

		$this->configurator->tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$this->assertSame(
			$expected,
			$this->configurator->getRenderer()->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() preserves keys and order
	*/
	public function testMultiOrder()
	{
		$parsed = [
			'<rt>1<B>One</B></rt>',
			'<pt>2Two</pt>',
			'p3' => '<rt>3<B>Three</B></rt>',
			'p4' => '<pt>4Four</pt>',
			'<rt>5<B>Five</B></rt>',
			'<pt>6Six</pt>'
		];

		$expected = [
			'1<b>One</b>',
			'2Two',
			'p3' => '3<b>Three</b>',
			'p4' => '4Four',
			'5<b>Five</b>',
			'6Six'
		];

		$this->configurator->tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$this->assertSame(
			$expected,
			$this->configurator->getRenderer()->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() renders multi-line text in HTML
	*/
	public function testMultiMultiPlainTextHTML()
	{
		$this->configurator->stylesheet->setOutputMethod('html');

		$parsed   = ['<pt>One<br/>two</pt>'];
		$expected = ['One<br>two'];

		$this->assertSame(
			$expected,
			$this->configurator->getRenderer()->renderMulti($parsed)
		);
	}

	/**
	* @testdox renderMulti() renders multi-line text in XHTML
	*/
	public function testMultiMultiPlainTextXHTML()
	{
		$this->configurator->stylesheet->setOutputMethod('xml');

		$parsed   = ['<pt>One<br/>two</pt>'];
		$expected = ['One<br/>two'];

		$this->assertSame(
			$expected,
			$this->configurator->getRenderer()->renderMulti($parsed)
		);
	}

	/**
	* @testdox setParameter() sets the value of a parameter
	*/
	public function testSetParameter()
	{
		$this->configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/>';
		$this->configurator->stylesheet->parameters->add('foo');

		$renderer = $this->configurator->getRenderer();
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
		$this->configurator->tags->add('X')->defaultTemplate
			= '<xsl:value-of select="$foo"/><xsl:value-of select="$bar"/>';
		$this->configurator->stylesheet->parameters->add('foo');
		$this->configurator->stylesheet->parameters->add('bar');

		$renderer = $this->configurator->getRenderer();
		$renderer->setParameters([
			'foo' => 'FOO',
			'bar' => 'BAR'
		]);

		$this->assertSame(
			'FOOBAR',
			$renderer->render('<rt><X/></rt>')
		);
	}

	/**
	* @testdox setParameter() accepts values that contain both types of quotes
	*/
	public function testSetParameterBothQuotes()
	{
		$this->configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/>';
		$this->configurator->stylesheet->parameters->add('foo');
		$renderer = $this->configurator->getRenderer();

		$values = [
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		];

		foreach ($values as $value)
		{
			$renderer->setParameter('foo', $value);
			$this->assertSame($value, $renderer->render('<rt><X/></rt>'));
		}
	}
}