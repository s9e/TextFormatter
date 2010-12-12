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
 
class deleteNodesTest extends \PHPUnit_Framework_TestCase
{
	public function testRootContext()
	{
		$xpath = '//*[@remove="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 remove="1" />
				<child2 remove="0" />
				<child3>
					<grandchild remove="1" />
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_result = new SimpleDOM(
			'<root>
				<child2 remove="0" />
				<child3 />
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_return = 2;

		$return = $root->deleteNodes($xpath);

		$this->assertXmlStringEqualsXmlString($expected_result->asXML(), $root->asXML());
		$this->assertSame($expected_return, $return);
	}

	public function testChildContext()
	{
		$xpath = './/*[@remove="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 remove="1" />
				<child2 remove="0" />
				<child3>
					<grandchild>
						<grandgrandchild remove="1" />
					</grandchild>
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_result = new SimpleDOM(
			'<root>
				<child1 remove="1" />
				<child2 remove="0" />
				<child3>
					<grandchild />
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_return = 1;

		$return = $root->child3->deleteNodes($xpath);

		$this->assertXmlStringEqualsXmlString($expected_result->asXML(), $root->asXML());
		$this->assertSame($expected_return, $return);
	}

	public function testChildContextNoMatches()
	{
		$xpath = './*[@remove="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 remove="1" />
				<child2 remove="0" />
				<child3>
					<grandchild>
						<grandgrandchild remove="1" />
					</grandchild>
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_result = clone $root;
		$expected_return = 0;

		$return = $root->child3->deleteNodes($xpath);

		$this->assertXmlStringEqualsXmlString($expected_result->asXML(), $root->asXML());
		$this->assertSame($expected_return, $return);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidArgumentType()
	{
		$root = new SimpleDOM('<root />');
		$root->deleteNodes(false);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXPath()
	{
		$root = new SimpleDOM('<root />');
		$root->deleteNodes('????');
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testRootNodeCannotDeleted()
	{
		$xpath = '//*[@remove="1"]';

		$root = new SimpleDOM(
			'<root remove="1" />',
			LIBXML_NOBLANKS
		);

		$root->DeleteNodes($xpath);
	}
}