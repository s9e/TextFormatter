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
 
class removeTest extends \PHPUnit_Framework_TestCase
{
	public function testRemoveChild()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		$expected_return = clone $root->child;
		$return = $root->child->remove();

		$this->assertXmlStringEqualsXmlString('<root />', $root->asXML());
		$this->assertTrue($return instanceof SimpleDOM);
		$this->assertEquals($expected_return, $return);
	}

	public function testRemoveGrandchild()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		$expected_return = clone $root->child->grandchild;
		$return = $root->child->grandchild->remove();

		$this->assertXmlStringEqualsXmlString('<root><child /></root>', $root->asXML());
		$this->assertTrue($return instanceof SimpleDOM);
		$this->assertEquals($expected_return, $return);
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testRoot()
	{
		$root = new SimpleDOM('<root />');
		$root->remove();
	}
}