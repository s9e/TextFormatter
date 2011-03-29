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
 
class firstChildTest extends \PHPUnit_Framework_TestCase
{
	public function testChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$child1 = $root->firstChild();

		$this->assertTrue($child1 instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child1),
			dom_import_simplexml($child1)
		);
	}

	public function testGrandchild()
	{
		$root = new SimpleDOM('<root><child1><grandchild /></child1><child2 /><child3 /></root>');
		$grandchild = $root->child1->firstChild();

		$this->assertTrue($grandchild instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child1->grandchild),
			dom_import_simplexml($grandchild)
		);
	}

	public function testNoChild()
	{
		$root = new SimpleDOM('<root />');
		$this->assertNull($root->firstChild());
	}

	public function testNoGrandchild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$grandchild = $root->child1->firstChild();

		$this->assertNull($grandchild);
	}
}