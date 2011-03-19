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
 
class nextSiblingTest extends \PHPUnit_Framework_TestCase
{
	public function testRoot()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$this->assertNull($root->nextSibling());
	}

	public function testFirstChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child2 = $root->child1->nextSibling();

		$this->assertTrue($child2 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child2),
			dom_import_simplexml($child2)
		);
	}

	public function testMiddleChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child3 = $root->child2->nextSibling();

		$this->assertTrue($child3 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child3),
			dom_import_simplexml($child3)
		);
	}

	public function testLastChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$this->assertNull($root->child3->nextSibling());
	}
}