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
 
class copyAttributesFromTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 />
				<child2 a="aval" b="bval" />
			</root>'
		);

		$root->child1->copyAttributesFrom($root->child2);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 a="aval" b="bval" />
				<child2 a="aval" b="bval" />
			</root>',
			$root->asXML()
		);
	}

	public function testAttributesAreCopiedAcrossDocuments()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 />
			</root>'
		);

		$other = new SimpleDOM(
			'<root>
				<child2 a="aval" b="bval" />
			</root>'
		);

		$root->child1->copyAttributesFrom($other->child2);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 a="aval" b="bval" />
			</root>',
			$root->asXML()
		);
	}

	public function testNSAttributesAreCopied()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 />
				<child2 xmlns:foo="urn:foo" foo:a="foo:aval" a="aval" b="bval" />
			</root>'
		);

		$root->child1->copyAttributesFrom($root->child2);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 xmlns:foo="urn:foo" foo:a="foo:aval" a="aval" b="bval" />
				<child2 xmlns:foo="urn:foo" foo:a="foo:aval" a="aval" b="bval" />
			</root>',
			$root->asXML()
		);
	}

	public function testExistingAttributesAreOverwrittenByDefault()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 a="old" />
				<child2 a="aval" b="bval" />
			</root>'
		);

		$root->child1->copyAttributesFrom($root->child2);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 a="aval" b="bval" />
				<child2 a="aval" b="bval" />
			</root>',
			$root->asXML()
		);
	}

	public function testExistingAttributesCanBePreserved()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 a="old" />
				<child2 a="aval" b="bval" />
			</root>'
		);

		$root->child1->copyAttributesFrom($root->child2, false);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 a="old" b="bval" />
				<child2 a="aval" b="bval" />
			</root>',
			$root->asXML()
		);
	}

	public function testNSDeclarationsAreNotAttributesAndAreNotCopiedUnlessNeeded()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 />
				<child2 a="aval" b="bval" xmlns:foo="urn:foo" />
			</root>'
		);

		$root->child1->copyAttributesFrom($root->child2);

		$this->assertXmlStringEqualsXmlString(
			'<root>
				<child1 a="aval" b="bval" />
				<child2 a="aval" b="bval" xmlns:foo="urn:foo" />
			</root>',
			$root->asXML()
		);
	}
}