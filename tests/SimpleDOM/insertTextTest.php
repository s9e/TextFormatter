<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class insertTextTest extends \PHPUnit_Framework_TestCase
{
	public function testAppendIsDefault()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/>TEST</root>';

		$root->insertText('TEST');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAppend()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/>TEST</root>';

		$root->insertText('TEST', 'append');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testBefore()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root>TEST<child1 /><child2 /><child3/></root>';

		$root->child1->insertText('TEST', 'before');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAfterWithNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 />TEST<child2 /><child3/></root>';

		$root->child1->insertText('TEST', 'after');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAfterWithoutNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/>TEST</root>';

		$root->child3->insertText('TEST', 'after');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testEmptyRoot()
	{
		$root = new SimpleDOM('<root />');

		$return = $root->insertText('foo');

		$this->assertXmlStringEqualsXmlString('<root>foo</root>', $root->asXML());
		$this->assertSame($root, $return);
	}

	public function testNonEmptyRoot()
	{
		$root = new SimpleDOM('<root>foo</root>');

		$return = $root->insertText('bar');

		$this->assertXmlStringEqualsXmlString('<root>foobar</root>', $root->asXML());
		$this->assertSame($root, $return);
	}

	public function testNonEmptyRootWithChild()
	{
		$root = new SimpleDOM('<root>foo<bar /></root>');

		$return = $root->insertText('baz');

		$this->assertXmlStringEqualsXmlString('<root>foo<bar />baz</root>', $root->asXML());
		$this->assertSame($root, $return);
	}

	public function testChild()
	{
		$root = new SimpleDOM('<root><foo /></root>');
		$foo = $root->foo;

		$return = $foo->insertText('bar')->insertText('baz');

		$this->assertXmlStringEqualsXmlString('<root><foo>barbaz</foo></root>', $root->asXML());
		$this->assertSame($foo, $return);
	}
}