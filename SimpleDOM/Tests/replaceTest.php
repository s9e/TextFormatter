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
 
class replaceTest extends \PHPUnit_Framework_TestCase
{
	public function testReplaceFirstChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$expected_return = clone $root->child1;
		$return = $root->child1->replace($new);

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
		$return = $root->child2->replace($new);

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
		$return = $root->child3->replace($new);

		$this->assertXmlStringEqualsXmlString('<root><child1 /><child2 /><new /></root>', $root->asXML());
		$this->assertEquals($expected_return, $return);
		$this->assertNotSame(
			dom_import_simplexml($return),
			dom_import_simplexml($new)
		);
	}

	public function testRoot()
	{
		$root = new SimpleDOM('<root />');
		$new = new SimpleDOM('<new />');

		$expected_result = clone $new;
		$expected_return = clone $root;

		$return = $root->replace($new);
		
		$this->assertEquals($expected_result, $root);
		$this->assertEquals($expected_return, $return);
	}
}