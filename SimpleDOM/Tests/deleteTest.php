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
 
class deleteTest extends \PHPUnit_Framework_TestCase
{
	public function testDeleteChild()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		$root->child->delete();

		$this->assertXmlStringEqualsXmlString('<root />', $root->asXML());
	}

	public function testDeleteGrandchild()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		$root->child->grandchild->delete();

		$this->assertXmlStringEqualsXmlString('<root><child /></root>', $root->asXML());
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testRoot()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$root->delete();
	}
}