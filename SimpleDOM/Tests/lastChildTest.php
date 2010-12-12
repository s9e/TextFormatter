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
 
class lastChildTest extends \PHPUnit_Framework_TestCase
{
	public function testChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child3 = $root->lastChild();

		$this->assertTrue($child3 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child3),
			dom_import_simplexml($child3)
		);
	}

	public function testGrandchild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3><grandchild /></child3></root>');
		$grandchild = $root->child3->lastChild();

		$this->assertTrue($grandchild instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child3->grandchild),
			dom_import_simplexml($grandchild)
		);
	}

	public function testNoChild()
	{
		$root = new SimpleDOM('<root />');
		$this->assertNull($root->lastChild());
	}

	public function testNoGrandchild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$grandchild = $root->child3->lastChild();

		$this->assertNull($grandchild);
	}
}