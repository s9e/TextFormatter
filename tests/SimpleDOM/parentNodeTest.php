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
 
class parentNodeTest extends \PHPUnit_Framework_TestCase
{
	public function testRoot()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$parent = $root->parentNode();

		/**
		* When asked for the root node's parent, DOM returns the root node itself
		*/
		$this->assertTrue($parent instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root),
			dom_import_simplexml($parent)
		);
	}

	public function testChild()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$parent = $root->child->parentNode();

		$this->assertTrue($parent instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root),
			dom_import_simplexml($parent)
		);
	}

	public function testGrandchild()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');
		$parent = $root->child->grandchild->parentNode();

		$this->assertTrue($parent instanceof SimpleDOM);
		$this->assertSame(
			dom_import_simplexml($root->child),
			dom_import_simplexml($parent)
		);
	}
}