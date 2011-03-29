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
 
class insertXMLTest extends \PHPUnit_Framework_TestCase
{
	public function testChild()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$new = '<new />';

		$root->insertXML($new);

		$this->assertXmlStringEqualsXmlString('<root><child /><new /></root>', $root->asXML());
	}

	public function testGrandchild()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$root->child->insertXML('<new />');

		$this->assertXmlStringEqualsXmlString(
			'<root><child><new /></child></root>',
			$root->asXML()
		);
	}

	public function testTextNode()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$root->insertXML('my text node');

		$this->assertXmlStringEqualsXmlString('<root><child />my text node</root>', $root->asXML());
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testInsertXMLOutsideOfRootNode()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$root->insertXML('my text node', 'after');
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXML()
	{
		$root = new SimpleDOM('<root><child /></root>');
		$root->insertXML('<bad><xml>');
	}
}