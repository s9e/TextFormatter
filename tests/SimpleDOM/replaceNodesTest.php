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
 
class replaceNodesTest extends \PHPUnit_Framework_TestCase
{
	public function testRootContext()
	{
		$xpath = '//*[@replace="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 replace="1" />
				<child2 replace="0" />
				<child3>
					<grandchild replace="1" />
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$new = new SimpleDOM('<new />');

		$expected_result = new SimpleDOM(
			'<root>
				<new />
				<child2 replace="0" />
				<child3>
					<new />
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_return = array(
			clone $root->child1,
			clone $root->child3->grandchild
		);

		$return = $root->replaceNodes($xpath, $new);

		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
	}

	public function testChildContext()
	{
		$xpath = './/*[@replace="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 replace="1" />
				<child2 replace="0" />
				<child3>
					<grandchild>
						<grandgrandchild replace="1" />
					</grandchild>
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$new = new SimpleDOM('<new />');

		$expected_result = new SimpleDOM(
			'<root>
				<child1 replace="1" />
				<child2 replace="0" />
				<child3>
					<grandchild>
						<new />
					</grandchild>
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_return = array(
			clone $root->child3->grandchild->grandgrandchild
		);

		$return = $root->child3->replaceNodes($xpath, $new);

		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
	}

	public function testChildContextNoMatches()
	{
		$xpath = './*[@replace="1"]';

		$root = new SimpleDOM(
			'<root>
				<child1 replace="1" />
				<child2 replace="0" />
				<child3>
					<grandchild>
						<grandgrandchild replace="1" />
					</grandchild>
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$new = new SimpleDOM('<new />');

		$expected_result = clone $root;
		$expected_return = array();

		$return = $root->child3->replaceNodes($xpath, $new);

		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testRootNodeCannotBeReplaced()
	{
		$root = new SimpleDOM('<root />');
		$new  = new SimpleDOM('<new />');

		$root->replaceNodes('/root', $new);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidArgumentType()
	{
		$root = new SimpleDOM('<root />');
		$new = new SimpleDOM('<new />');

		$root->replaceNodes(false, $new);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXPath()
	{
		$root = new SimpleDOM('<root />');
		$new = new SimpleDOM('<new />');

		$root->replaceNodes('????', $new);
	}
}