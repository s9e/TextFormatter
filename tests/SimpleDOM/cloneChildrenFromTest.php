<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class cloneChildrenFromTest extends \PHPUnit_Framework_TestCase
{
	public function testMultipleDocuments()
	{
		$doc1 = new SimpleDOM('<doc1 />');
		$doc2 = new SimpleDOM('<doc2><child1 /><child2 /><child3 /></doc2>');

		$doc1->cloneChildrenFrom($doc2);

		$this->assertXmlStringEqualsXmlString(
			'<doc1><child1 /><child2 /><child3 /></doc1>',
			$doc1->asXML()
		);
	}

	public function testMultipleDocumentsNS()
	{
		$doc1 = new SimpleDOM('<doc1 />');
		$doc2 = new SimpleDOM('<doc2 xmlns:ns="urn:ns"><ns:child1 /><child2 /><child3 /></doc2>');

		$doc1->cloneChildrenFrom($doc2);

		$this->assertXmlStringEqualsXmlString(
			'<doc1><ns:child1  xmlns:ns="urn:ns"/><child2 /><child3 /></doc1>',
			$doc1->asXML()
		);
	}

	public function testNodesAreNotBoundToSourceDocument()
	{
		$doc1 = new SimpleDOM('<doc1 />');
		$doc2 = new SimpleDOM('<doc2><child1 /><child2 /><child3 /></doc2>');

		$doc1->cloneChildrenFrom($doc2);

		$doc1->child1['doc'] = 1;
		$doc2->child1['doc'] = 2;

		$this->assertXmlStringEqualsXmlString(
			'<doc1><child1 doc="1" /><child2 /><child3 /></doc1>',
			$doc1->asXML()
		);

		$this->assertXmlStringEqualsXmlString(
			'<doc2><child1 doc="2" /><child2 /><child3 /></doc2>',
			$doc2->asXML()
		);
	}

	public function testCloningIsDeepByDefault()
	{
		$doc1 = new SimpleDOM('<doc1 />');
		$doc2 = new SimpleDOM(
			'<doc2>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</doc2>'
		);

		$doc1->cloneChildrenFrom($doc2);

		$this->assertXmlStringEqualsXmlString(
			'<doc1>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</doc1>',
			$doc1->asXML()
		);
	}

	public function testCloningCanBeShallow()
	{
		$doc1 = new SimpleDOM('<doc1 />');
		$doc2 = new SimpleDOM(
			'<doc2>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</doc2>'
		);

		$doc1->cloneChildrenFrom($doc2, false);

		$this->assertXmlStringEqualsXmlString(
			'<doc1>
				<child1 />
				<child2 />
				<child3 />
			</doc1>',
			$doc1->asXML()
		);
	}

	public function testCloningFromSameNode()
	{
		$node = new SimpleDOM(
			'<node>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</node>'
		);

		$node->cloneChildrenFrom($node, true);

		$this->assertXmlStringEqualsXmlString(
			'<node>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>

				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</node>',
			$node->asXML()
		);
	}

	public function testCloningFromDescendantNode()
	{
		$node = new SimpleDOM(
			'<node>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</node>'
		);

		$node->cloneChildrenFrom($node->child1, true);

		$this->assertXmlStringEqualsXmlString(
			'<node>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>

				<granchild1 />
			</node>',
			$node->asXML()
		);
	}

	public function testCloningFromAscendantNode()
	{
		$node = new SimpleDOM(
			'<node>
				<child1><granchild1 /></child1>
				<child2 />
				<child3><granchild3 /></child3>
			</node>'
		);

		$node->child1->cloneChildrenFrom($node, true);

		$this->assertXmlStringEqualsXmlString(
			'<node>
				<child1>
					<granchild1 />

					<child1><granchild1 /></child1>
					<child2 />
					<child3><granchild3 /></child3>
				</child1>
				<child2 />
				<child3><granchild3 /></child3>
			</node>',
			$node->asXML()
		);
	}
}