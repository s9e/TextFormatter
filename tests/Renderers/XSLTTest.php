<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension xsl
* @covers s9e\TextFormatter\Renderer
* @covers s9e\TextFormatter\Renderers\XSLT
*/
class XSLTTest extends Test
{
	/**
	* @testdox Renders plain text
	*/
	public function testPlainText()
	{
		$xml = '<t>Plain text</t>';

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
		$xml = '<t>One<br/>two</t>';

		$this->configurator->rendering->type = 'html';

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
		$xml = '<t>One<br/>two</t>';

		$this->configurator->rendering->type = 'xhtml';

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
		$xml = '<r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>';

		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

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
		$parsed = array(
			'<r>1Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>',
			'<t>2Plain text</t>',
			'<r>3Hello <B><s>[b]</s>world<e>[/b]</e></B>!</r>',
			'<t>4Plain text</t>'
		);

		$expected = array(
			'1Hello <b>world</b>!',
			'2Plain text',
			'3Hello <b>world</b>!',
			'4Plain text'
		);

		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

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
		$parsed = array(
			'<r>1<B>One</B></r>',
			'<t>2Two</t>',
			'p3' => '<r>3<B>Three</B></r>',
			'p4' => '<t>4Four</t>',
			'<r>5<B>Five</B></r>',
			'<t>6Six</t>'
		);

		$expected = array(
			'1<b>One</b>',
			'2Two',
			'p3' => '3<b>Three</b>',
			'p4' => '4Four',
			'5<b>Five</b>',
			'6Six'
		);

		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

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
		$this->configurator->rendering->type = 'html';

		$parsed   = array('<t>One<br/>two</t>');
		$expected = array('One<br>two');

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
		$this->configurator->rendering->type = 'xhtml';

		$parsed   = array('<t>One<br/>two</t>');
		$expected = array('One<br/>two');

		$this->assertSame(
			$expected,
			$this->configurator->getRenderer()->renderMulti($parsed)
		);
	}

	/**
	* @testdox getParameter() returns the default value of a parameter
	*/
	public function testGetParameterDefault()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo', 'bar');

		$renderer = $this->configurator->getRenderer();

		$this->assertSame('bar', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameter() returns the set value of a parameter
	*/
	public function testGetParameterSet()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo', 'bar');

		$renderer = $this->configurator->getRenderer();
		$renderer->setParameter('foo', 'baz');

		$this->assertSame('baz', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameter() returns an empty string for undefined parameters
	*/
	public function testGetParameterUndefined()
	{
		$renderer = $this->configurator->getRenderer();

		$this->assertSame('', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameters() returns the values of all parameters, defined and set
	*/
	public function testGetParameters()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('bar', 'BAR');

		$renderer = $this->configurator->getRenderer();
		$renderer->setParameter('baz', 'BAZ');

		$this->assertEquals(
			array('foo' => '', 'bar' => 'BAR', 'baz' => 'BAZ'),
			$renderer->getParameters()
		);
	}

	/**
	* @testdox setParameter() sets the value of a parameter
	*/
	public function testSetParameter()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo');

		$renderer = $this->configurator->getRenderer();
		$renderer->setParameter('foo', 'bar');

		$this->assertSame(
			'bar',
			$renderer->render('<r><X/></r>')
		);
	}

	/**
	* @testdox setParameters() sets the values of any number of parameters in an associative array
	*/
	public function testSetParameters()
	{
		$this->configurator->tags->add('X')->template
			= '<xsl:value-of select="$foo"/><xsl:value-of select="$bar"/>';
		$this->configurator->rendering->parameters->add('foo');
		$this->configurator->rendering->parameters->add('bar');

		$renderer = $this->configurator->getRenderer();
		$renderer->setParameters(array(
			'foo' => 'FOO',
			'bar' => 'BAR'
		));

		$this->assertSame(
			'FOOBAR',
			$renderer->render('<r><X/></r>')
		);
	}

	public function _ignore()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo');
		$renderer = $this->configurator->getRenderer();

		$values = array(
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		);

		foreach ($values as $value)
		{
			$renderer->setParameter('foo', $value);
			$this->assertSame($value, $renderer->render('<r><X/></r>'));
		}
	}

	/**
	* @testdox Custom parameters are properly saved and restored after serialization
	*/
	public function testGetParameterUnserialized()
	{
		$this->configurator->rendering->parameters['x'] = 'y';
		$renderer = $this->configurator->getRenderer();
		$renderer->setParameter('foo', 'xxx');

		$this->assertEquals(
			array('foo' => 'xxx', 'x' => 'y'),
			unserialize(serialize($renderer))->getParameters()
		);
	}

	/**
	* @testdox Is not vulnerable to XXE
	*/
	public function testXXE()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
		     . '<!DOCTYPE foo [<!ELEMENT r ANY><!ENTITY xxe SYSTEM "data:text/plain,Hello">]>'
		     . '<r>x&xxe;y</r>';

		$this->assertSame(
			'xy',
			$this->configurator->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox Is serializable
	*/
	public function testSerializable()
	{
		$renderer = $this->configurator->getRenderer();

		$this->assertEquals(
			$renderer,
			unserialize(serialize($renderer))
		);
	}

	/**
	* @testdox Does not serialize the XSLTProcessor instance
	*/
	public function testSerializableNoProc()
	{
		$renderer = $this->configurator->getRenderer();
		$renderer->render('<r>..</r>');

		$this->assertNotContains(
			'XSLTProcessor',
			serialize($renderer)
		);
	}

	/**
	* @testdox Preserves other properties during serialization
	*/
	public function testSerializableCustomProps()
	{
		$renderer = $this->configurator->getRenderer();
		$renderer->foo = 'bar';

		$this->assertAttributeEquals(
			'bar',
			'foo',
			unserialize(serialize($renderer))
		);
	}

	/**
	* @testdox Renders multi-line text in HTML after un/serialization
	*/
	public function testUnserializedMultiLineTextHTML()
	{
		$xml = '<t>One<br/>two</t>';

		$this->configurator->rendering->type = 'html';
		$renderer = unserialize(serialize($this->configurator->getRenderer()));

		$this->assertSame(
			'One<br>two',
			$renderer->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in XHTML after un/serialization
	*/
	public function testUnserializedMultiLineTextXHTML()
	{
		$xml = '<t>One<br/>two</t>';

		$this->configurator->rendering->type = 'xhtml';
		$renderer = unserialize(serialize($this->configurator->getRenderer()));

		$this->assertSame(
			'One<br/>two',
			$renderer->render($xml)
		);
	}

	/**
	* @testdox setParameter() accepts values that contain both types of quotes but replaces ASCII character " with Unicode character 0xFF02 because of https://bugs.php.net/64137
	*/
	public function testSetParameterBothQuotes()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo');
		$renderer = $this->configurator->getRenderer();

		$values = array(
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		);

		foreach ($values as $value)
		{
			$renderer->setParameter('foo', $value);
			$this->assertSame(
				str_replace('"', "\xEF\xBC\x82", $value),
				$renderer->render('<r><X/></r>')
			);
		}
	}

	/**
	* @testdox Does not output </embed> end tags
	*/
	public function testNoEmbedEndTag()
	{
		$this->configurator->tags->add('X')->template
			= '<object><embed src="foo"/></object>';

		$this->assertSame(
			'<object><embed src="foo"></object>',
			$this->configurator->getRenderer()->render('<r><X/></r>')
		);
	}
}