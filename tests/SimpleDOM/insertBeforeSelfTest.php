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
 
class insertBeforeSelfTest extends \PHPUnit_Framework_TestCase
{
	public function testBeforeFirstChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->child1->insertBeforeSelf($new);

		$this->assertXmlStringEqualsXmlString($root->asXML(), '<root><new /><child1 /><child2 /><child3 /></root>');
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
		);
	}

	public function testBeforeMiddleChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->child2->insertBeforeSelf($new);

		$this->assertXmlStringEqualsXmlString($root->asXML(), '<root><child1 /><new /><child2 /><child3 /></root>');
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
		);
	}

	public function testBeforeLastChild()
	{
		$root = new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->child3->insertBeforeSelf($new);

		$this->assertXmlStringEqualsXmlString($root->asXML(), '<root><child1 /><child2 /><new /><child3 /></root>');
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
		);
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testRoot()
	{
		$root = new SimpleDOM('<root />');
		$new = new SimpleDOM('<new />');

		$root->insertBeforeSelf($new);
	}
}