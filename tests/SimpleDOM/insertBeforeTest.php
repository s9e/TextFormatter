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
 
class insertBeforeTest extends \PHPUnit_Framework_TestCase
{
	public function testBeforeFirstChild()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->insertBefore($new, $root->child);

		$this->assertXmlStringEqualsXmlString('<root><new /><child /></root>', $root->asXML());
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
		);
	}

	public function testBeforeLastChild()
	{
		$root = new SimpleDOM('<root><child /><otherchild /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->insertBefore($new, $root->otherchild);

		$this->assertXmlStringEqualsXmlString('<root><child /><new /><otherchild /></root>', $root->asXML());
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
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
			$root->insertBefore($new, $root->child->grandchild);
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
		$root = new SimpleDOM('<root><child><grandchild /></child></root>');
		$new = new SimpleDOM('<new />');
		$node = new SimpleDOM('<node />');

		try
		{
			$root->insertBefore($new, $node);
		}
		catch (DOMException $e)
		{
			$this->assertSame(DOM_NOT_FOUND_ERR, $e->code);
			throw $e;
		}
	}

	public function testNoRef()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$new = new SimpleDOM('<new />');

		$return = $root->insertBefore($new);

		$this->assertXmlStringEqualsXmlString('<root><child /><new /></root>', $root->asXML());
		$this->assertSame(
			dom_import_simplexml($root->new),
			dom_import_simplexml($return)
		);
	}
}