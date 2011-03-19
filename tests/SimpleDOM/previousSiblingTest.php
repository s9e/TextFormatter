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
 
class previousSiblingTest extends \PHPUnit_Framework_TestCase
{
	public function testRoot()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$this->assertNull($root->previousSibling());
	}

	public function testFirstChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$this->assertNull($root->child1->previousSibling());
	}

	public function testMiddleChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child1 = $root->child2->previousSibling();

		$this->assertTrue($child1 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child1),
			dom_import_simplexml($child1)
		);
	}

	public function testLastChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child2 = $root->child3->previousSibling();

		$this->assertTrue($child2 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child2),
			dom_import_simplexml($child2)
		);
	}
}