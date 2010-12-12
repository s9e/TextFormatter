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
 
class removeNodesTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException BadMethodCallException
	*/
	public function testRootNodeCannotBeRemoved()
	{
		$xpath = '//*[@remove="1"]';

		$root = new SimpleDOM(
			'<root remove="1" />',
			LIBXML_NOBLANKS
		);

		$root->removeNodes($xpath);
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
			</root>'
		);

		$expected_result = new SimpleDOM(
			'<root>
				<child1 remove="1" />
				<child2 remove="0" />
				<child3>
					<grandchild />
				</child3>
			</root>'
		);

		$expected_return = array(
			clone $root->child3->grandchild->grandgrandchild
		);

		$return = $root->child3->removeNodes($xpath);

		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
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
		$expected_return = array();

		$return = $root->child3->removeNodes($xpath);

		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidArgumentType()
	{
		$root = new SimpleDOM('<root />');
		$root->removeNodes(false);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXPath()
	{
		$root = new SimpleDOM('<root />');
		$root->removeNodes('????');
	}
}