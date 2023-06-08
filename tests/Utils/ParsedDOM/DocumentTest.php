<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Utils\ParsedDOM;

use s9e\TextFormatter\Utils\ParsedDOM;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils\ParsedDOM\Document
*/
class DocumentTest extends Test
{
	/**
	* @testdox createTagElement('b') normalizes tag name to 'B'
	*/
	public function testCreateTagElementNormalizeTagName()
	{
		$dom = ParsedDOM::loadXML('<r/>');
		$dom->documentElement->appendChild(
			$dom->createTagElement('b')
		);

		$this->assertXmlStringEqualsXmlString(
			'<r><B/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox createTagElement('foo:BAR') creates a namespaced tag
	*/
	public function testCreateTagElementNamespace()
	{
		$dom = ParsedDOM::loadXML('<r/>');
		$dom->documentElement->appendChild(
			$dom->createTagElement('foo:BAR')
		);

		$this->assertXmlStringEqualsXmlString(
			'<r xmlns:foo="urn:s9e:TextFormatter:foo"><foo:BAR/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox createTagElement() sets attributes
	*/
	public function testCreateTagElementAttributes()
	{
		$dom = ParsedDOM::loadXML('<r/>');
		$dom->documentElement->appendChild(
			$dom->createTagElement('X', ['b' => '<>&"', 'a' => 1])
		);

		$this->assertXmlStringEqualsXmlString(
			'<r><X a="1" b="&lt;&gt;&amp;&quot;"/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox createTagElement() normalizes attribute names
	*/
	public function testCreateTagElementNormalizeAttributes()
	{
		$dom = ParsedDOM::loadXML('<r/>');
		$dom->documentElement->appendChild(
			$dom->createTagElement('X', ['B' => '<>&"', 'A' => 1])
		);

		$this->assertXmlStringEqualsXmlString(
			'<r><X a="1" b="&lt;&gt;&amp;&quot;"/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalizeDocument() normalizes elements
	*/
	public function testNormalizeDocumentElements()
	{
		$dom = ParsedDOM::loadXML('<r><X><s/>..<e/></X></r>');
		$dom->normalizeDocument();

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalizeDocument() removes superfluous namespaces
	*/
	public function testNormalizeDocumentNamespaces()
	{
		$dom = ParsedDOM::loadXML('<r xmlns:foo="urn:foo"><X/></r>');
		$dom->normalizeDocument();

		$this->assertXmlStringEqualsXmlString(
			'<r><X/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox __toString() returns a string without an XML declaration
	*/
	public function testToStringNoXmlDeclaration()
	{
		$dom = ParsedDOM::loadXML('<r><X>..</X></r>');

		$this->assertEquals(
			'<r><X>..</X></r>',
			(string) $dom
		);
	}

	/**
	* @testdox __toString() returns "<t></t>" for completely empty content
	*/
	public function testToStringEmpty()
	{
		$dom = ParsedDOM::loadXML('<r></r>');

		$this->assertEquals(
			'<t></t>',
			(string) $dom
		);
	}

	/**
	* @testdox __toString() removes empty markup
	*/
	public function testToStringNoEmptyMarkup()
	{
		$dom = ParsedDOM::loadXML('<r><X><s/>..<e/></X></r>');

		$this->assertEquals(
			'<r><X>..</X></r>',
			(string) $dom
		);
	}
	/**
	* @testdox __toString() encodes SMP characters
	*/
	public function testToStringSMP()
	{
		$dom = ParsedDOM::loadXML('<t>&#128512;</t>');

		$this->assertEquals(
			'<t>&#128512;</t>',
			(string) $dom
		);
	}

}