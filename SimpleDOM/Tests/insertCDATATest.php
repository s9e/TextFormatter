<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../SimpleDOM.php';
 
class insertCDATATest extends \PHPUnit_Framework_TestCase
{
	public function testAppendIsDefault()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/><![CDATA[<TEST>]]></root>';

		$root->insertCDATA('<TEST>');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAppend()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/><![CDATA[<TEST>]]></root>';

		$root->insertCDATA('<TEST>', 'append');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testBefore()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><![CDATA[<TEST>]]><child1 /><child2 /><child3/></root>';

		$root->child1->insertCDATA('<TEST>', 'before');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAfterWithNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><![CDATA[<TEST>]]><child2 /><child3/></root>';

		$root->child1->insertCDATA('<TEST>', 'after');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testAfterWithoutNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/><![CDATA[<TEST>]]></root>';

		$root->child3->insertCDATA('<TEST>', 'after');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}

	public function testCDATACannotBeTricked()
	{
		$root		= new SimpleDOM('<root />');
		$expected	= '<root><![CDATA[<![CDATA[some data]]]]><![CDATA[>]]></root>';

		$root->insertCDATA('<![CDATA[some data]]>', 'append');

		$this->assertXmlStringEqualsXmlString($expected, $root->asXML());
	}
}