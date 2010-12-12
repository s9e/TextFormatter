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
 
class setAttributeNSTest extends \PHPUnit_Framework_TestCase
{
	public function testNS()
	{
		$node = new SimpleDOM('<node xmlns:ns="urn:ns" />');

		$node->setAttributeNS('urn:ns', 'a', 'aval');

		$this->assertXmlStringEqualsXmlString(
			'<node xmlns:ns="urn:ns" ns:a="aval" />',
			$node->asXML()
		);
	}

	/**
	* For some reason, DOMElement::setAttributeNS doesn't return anything
	*/
	/*
	public function testIsChainable()
	{
		$node = new SimpleDOM('<node xmlns:ns="urn:ns" />');

		$return = $node->setAttributeNS('urn:ns', 'a', 'aval');

		$this->assertEquals($node, $return);
		$this->assertTrue(dom_import_simplexml($node)->isSameNode(dom_import_simplexml($return)));
	}
	*/
}