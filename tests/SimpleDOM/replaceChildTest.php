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
 
class replaceChildTest extends \PHPUnit_Framework_TestCase
{
	public function testReplaceFirstChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$expected_return = clone $root->child1;
		$return = $root->replaceChild($new, $root->child1);

		$this->assertXmlStringEqualsXmlString('<root><new /><child2 /><child3 /></root>', $root->asXML());
		$this->assertEquals($expected_return, $return);
		$this->assertNotSame(
			dom_import_simplexml($return),
			dom_import_simplexml($new)
		);
	}

	public function testReplaceMiddleChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$expected_return = clone $root->child2;
		$return = $root->replaceChild($new, $root->child2);

		$this->assertXmlStringEqualsXmlString('<root><child1 /><new /><child3 /></root>', $root->asXML());
		$this->assertEquals($expected_return, $return);
		$this->assertNotSame(
			dom_import_simplexml($return),
			dom_import_simplexml($new)
		);
	}

	public function testReplaceLastChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$expected_return = clone $root->child3;
		$return = $root->replaceChild($new, $root->child3);

		$this->assertXmlStringEqualsXmlString('<root><child1 /><child2 /><new /></root>', $root->asXML());
		$this->assertEquals($expected_return, $return);
		$this->assertNotSame(
			dom_import_simplexml($return),
			dom_import_simplexml($new)
		);
	}

	/**
	* @expectedException DOMException
	*/
	public function testNotFound()
	{
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');
		$new = new SimpleDOM('<new />');

		try
		{
			$root->replaceChild($new, $root->child->grandchild);
		}
		catch (DOMException $e)
		{
			$this->assertSame(DOM_NOT_FOUND_ERR, $e->code);
			throw $e;
		}
	}

	public function testWrongDocument()
	{
		$root = new SimpleDOM('<root />');
		$new = new SimpleDOM('<new />');
		$node = new SimpleDOM('<node />');

		$this->assertFalse($root->replaceChild($new, $node));
	}
}