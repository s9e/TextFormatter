<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Utils\ParsedDOM;

use s9e\TextFormatter\Utils\ParsedDOM;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils\ParsedDOM\Element
*/
class ElementTest extends Test
{
	/**
	* @testdox normalize() removes empty s elements
	*/
	public function testNormalizeEmptyStart()
	{
		$dom = ParsedDOM::loadXML('<r><X><s/>..<e>*</e></X></r>');
		$dom->firstOf('//s')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..<e>*</e></X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalize() removes empty e elements
	*/
	public function testNormalizeEmptyEnd()
	{
		$dom = ParsedDOM::loadXML('<r><X><s>*</s>..<e></e></X></r>');
		$dom->firstOf('//e')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><X><s>*</s>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalize() removes empty i elements
	*/
	public function testNormalizeEmptyIgnore()
	{
		$dom = ParsedDOM::loadXML('<r><X>x<i/>x</X></r>');
		$dom->firstOf('//i')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><X>xx</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalize() does not remove e/i/s elements that only contain whitespace
	*/
	public function testNormalizeWhitespace()
	{
		$dom = ParsedDOM::loadXML('<r><X><s> </s><i> </i><e> </e></X></r>');
		$dom->firstOf('//s')->normalize();
		$dom->firstOf('//i')->normalize();
		$dom->firstOf('//e')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><X><s> </s><i> </i><e> </e></X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalize() does not remove empty E elements
	*/
	public function testNormalizeEmptyElement()
	{
		// The parser does remove <E></E> but preserves <E/>
		$dom = ParsedDOM::loadXML('<r><E/></r>');
		$dom->firstOf('//E')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><E/></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox normalize() sorts attributes by name
	*/
	public function testNormalizeAttributesAreSorted()
	{
		$dom = ParsedDOM::loadXML('<r><X _9="9" _10="10"/></r>');
		$dom->firstOf('//X')->normalize();

		$this->assertEquals(
			'<r><X _10="10" _9="9"/></r>',
			$dom->saveXML($dom->documentElement)
		);
	}

	/**
	* @testdox normalize() runs recursively
	*/
	public function testNormalizeRecursive()
	{
		$dom = ParsedDOM::loadXML('<r><X><Z><s/>..</Z></X></r>');
		$dom->firstOf('//X')->normalize();

		$this->assertXmlStringEqualsXmlString(
			'<r><X><Z>..</Z></X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupEnd('*') creates an 'e' element
	*/
	public function testSetMarkupEndCreate()
	{
		$dom = ParsedDOM::loadXML('<r><X>..</X></r>');
		$dom->firstOf('//X')->setMarkupEnd('*');

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..<e>*</e></X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupEnd('*') replaces the 'e' element if it exists
	*/
	public function testSetMarkupEndReplace()
	{
		$dom = ParsedDOM::loadXML('<r><X>..<e>[/b]</e></X></r>');
		$dom->firstOf('//X')->setMarkupEnd('*');

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..<e>*</e></X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupEnd('') removes the 'e' element
	*/
	public function testSetMarkupEndRemove()
	{
		$dom = ParsedDOM::loadXML('<r><X>..<e></e></X></r>');
		$dom->firstOf('//X')->setMarkupEnd('');

		$this->assertNull($dom->firstOf('//e'));

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupStart('*') creates an 's' element
	*/
	public function testSetMarkupStartCreate()
	{
		$dom = ParsedDOM::loadXML('<r><X>..</X></r>');
		$dom->firstOf('//X')->setMarkupStart('*');

		$this->assertXmlStringEqualsXmlString(
			'<r><X><s>*</s>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupStart('*') replaces the 's' element if it exists
	*/
	public function testSetMarkupStartReplace()
	{
		$dom = ParsedDOM::loadXML('<r><X><s>[/b]</s>..</X></r>');
		$dom->firstOf('//X')->setMarkupStart('*');

		$this->assertXmlStringEqualsXmlString(
			'<r><X><s>*</s>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox setMarkupStart('') removes the 's' element
	*/
	public function testSetMarkupStartRemove()
	{
		$dom = ParsedDOM::loadXML('<r><X><s>x</s>..</X></r>');
		$dom->firstOf('//X')->setMarkupStart('');

		$this->assertNull($dom->firstOf('//s'));

		$this->assertXmlStringEqualsXmlString(
			'<r><X>..</X></r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox unparse() does not remove any content
	*/
	public function testUnparseTextContent()
	{
		$dom = ParsedDOM::loadXML('<r>..<B><s>[b]</s>xx<e>[/b]</e></B>..</r>');
		$dom->firstOf('//B')->unparse();

		$this->assertXmlStringEqualsXmlString(
			'<r>..[b]xx[/b]..</r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox unparse() does not apply recursively
	*/
	public function testUnparseChildren()
	{
		$dom = ParsedDOM::loadXML('<r>..<B>x<I>x</I>x</B>..</r>');
		$dom->firstOf('//B')->unparse();

		$this->assertXmlStringEqualsXmlString(
			'<r>..x<I>x</I>x..</r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox replaceTag() replaces a tag and its attributes
	*/
	public function testReplaceTagAttributes()
	{
		$dom = ParsedDOM::loadXML('<r>..<URL url="old">..</URL>..</r>');
		$dom->firstOf('//URL')->replaceTag('A', ['href' => 'new']);

		$this->assertXmlStringEqualsXmlString(
			'<r>..<A href="new">..</A>..</r>',
			$dom->saveXML()
		);
	}

	/**
	* @testdox replaceTag() normalizes tag names and attribute names
	*/
	public function testReplaceTagNormalizeNames()
	{
		$dom = ParsedDOM::loadXML('<r>..<URL url="old">..</URL>..</r>');
		$dom->firstOf('//URL')->replaceTag('a', ['HREF' => 'new']);

		$this->assertXmlStringEqualsXmlString(
			'<r>..<A href="new">..</A>..</r>',
			$dom->saveXML()
		);
	}
}