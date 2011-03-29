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
 
class removeChildTest extends \PHPUnit_Framework_TestCase
{
	public function testRemoveDeep()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		$expected_return = clone $root->child;
		$return = $root->removeChild($root->child);

		$this->assertXmlStringEqualsXmlString('<root />', $root->asXML());
		$this->assertEquals($expected_return, $return);
	}

	/**
	* @expectedException DOMException
	*/
	public function testNotFound()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');

		try
		{
			$root->removeChild($root->child->grandchild);
		}
		catch (DOMException $e)
		{
			$this->assertSame(DOM_NOT_FOUND_ERR, $e->code);
			throw $e;
		}
	}

	/**
	* @expectedException DOMException
	*/
	public function testWrongDocument()
	{
		$root = new SimpleDOM('<root />');
		$node = new SimpleDOM('<node />');

		try
		{
			$root->removeChild($node);
		}
		catch (DOMException $e)
		{
			$this->assertSame(DOM_NOT_FOUND_ERR, $e->code);
			throw $e;
		}
	}
}