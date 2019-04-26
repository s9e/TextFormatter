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
		$xml = '<t>Plain text</t>';

		$this->assertSame(
			'Plain text',
			$this->configurator->rendering->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text
	*/
	public function testMultiLineTextHTML()
	{
		$xml = '<t>One<br/>two</t>';

		$this->assertSame(
			'One<br>two',
			$this->configurator->rendering->getRenderer()->render($xml)
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
			$this->configurator->rendering->getRenderer()->render($xml)
		);
	}

	/**
	* @testdox getParameter() returns the default value of a parameter
	*/
	public function testGetParameterDefault()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo', 'bar');

		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertSame('bar', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameter() returns the set value of a parameter
	*/
	public function testGetParameterSet()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo', 'bar');

		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->setParameter('foo', 'baz');

		$this->assertSame('baz', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameter() returns an empty string for undefined parameters
	*/
	public function testGetParameterUndefined()
	{
		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertSame('', $renderer->getParameter('foo'));
	}

	/**
	* @testdox getParameters() returns the values of all parameters, defined and set
	*/
	public function testGetParameters()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('bar', 'BAR');

		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->setParameter('baz', 'BAZ');

		$this->assertEquals(
			['foo' => '', 'bar' => 'BAR', 'baz' => 'BAZ'],
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

		$renderer = $this->configurator->rendering->getRenderer();
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

		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->setParameters([
			'foo' => 'FOO',
			'bar' => 'BAR'
		]);

		$this->assertSame(
			'FOOBAR',
			$renderer->render('<r><X/></r>')
		);
	}

	/**
	* @testdox setParameter() accepts values that contain both types of quotes
	*/
	public function testSetParameterBothQuotes()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';
		$this->configurator->rendering->parameters->add('foo');
		$renderer = $this->configurator->rendering->getRenderer();

		$values = [
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		];

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
		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->setParameter('foo', 'xxx');

		$this->assertEquals(
			['foo' => 'xxx', 'x' => 'y'],
			unserialize(serialize($renderer))->getParameters()
		);
	}

	/**
	* @testdox DTDs in the XML representation cause an exception to be thrown
	*/
	public function testDTD()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('DTD');

		$xml = '<!DOCTYPE foo [<!ELEMENT r ANY><!ENTITY foo "FOO">]>'
		     . '<r>x&foo;y</r>';

		$this->configurator->rendering->getRenderer()->render($xml);
	}

	/**
	* @testdox Comments in the XML representation cause an exception to be thrown
	*/
	public function testComment()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('comments');

		$this->configurator->rendering->getRenderer()->render('<r><!-- -->foo</r>');
	}

	/**
	* @testdox Processing instructions in the XML representation cause an exception to be thrown
	*/
	public function testPI()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Processing');

		$this->configurator->rendering->getRenderer()->render('<r><?pi ?>foo</r>');
	}

	/**
	* @testdox Is not vulnerable to XXE
	*/
	public function testXXE()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('DTD');

		$xml = '<!DOCTYPE foo [<!ELEMENT r ANY><!ENTITY xxe SYSTEM "data:text/plain,Hello">]>'
		     . '<r>x&xxe;y</r>';

		$this->configurator->rendering->getRenderer()->render($xml);
	}

	/**
	* @testdox Renders plain text with SMP character
	*/
	public function testRenderPlainSMP()
	{
		$this->assertSame(
			'😀',
			$this->configurator->rendering->getRenderer()->render('<t>&#128512;</t>')
		);
	}

	/**
	* @testdox Renders rich text with SMP character
	*/
	public function testRenderRichSMP()
	{
		$this->assertSame(
			'😀',
			$this->configurator->rendering->getRenderer()->render('<r>&#128512;</r>')
		);
	}

	/**
	* @testdox Renders rich text with SMP character encoded as hex
	*/
	public function testRenderRichSMPHex()
	{
		$this->assertSame(
			'😀',
			$this->configurator->rendering->getRenderer()->render('<r>&#x1F600;</r>')
		);
	}

	/**
	* @testdox Does not decode special chars in a plain text with a SMP character
	*/
	public function testRenderPlainSMPSpecial()
	{
		$this->assertSame(
			'&lt;😀&gt;',
			$this->configurator->rendering->getRenderer()->render('<t>&lt;&#128512;&gt;</t>')
		);
	}

	/**
	* @testdox Does not decode special chars in a rich text with a SMP character
	*/
	public function testRenderRichSMPSpecial()
	{
		$this->assertSame(
			'&lt;😀&gt;',
			$this->configurator->rendering->getRenderer()->render('<r>&lt;&#128512;&gt;</r>')
		);
	}

	/**
	* @testdox Does not decode special chars encoded as numeric entities in a plain text with a SMP character
	*/
	public function testRenderPlainSMPSpecialNumeric()
	{
		$this->assertSame(
			'&lt;😀',
			$this->configurator->rendering->getRenderer()->render('<t>&#0060;&#128512;</t>')
		);
	}

	/**
	* @testdox Does not decode special chars encoded as numeric entities in a rich text with a SMP character
	*/
	public function testRenderRichSMPSpecialNumeric()
	{
		$this->assertSame(
			'&lt;😀',
			$this->configurator->rendering->getRenderer()->render('<r>&#0060;&#128512;</r>')
		);
	}

	/**
	* @testdox Uses double quotes for attribute values
	*/
	public function testAttributeValuesDoubleQuotes()
	{
		$this->configurator->tags->add('X')->template = '<b data-d="{@d}" data-s="{@s}" data-x="{@x}"></b><i title=""></i>';
		$renderer = $this->configurator->rendering->getRenderer();

		$xml      = '<r><X d="&quot;" s="\'" x="&quot;\'"/></r>';
		$html     = $renderer->render($xml);
		$expected = '<b data-d="&quot;" data-s="\'" data-x="&quot;\'"></b><i title=""></i>';

		$this->assertSame($expected, $html);
	}
}