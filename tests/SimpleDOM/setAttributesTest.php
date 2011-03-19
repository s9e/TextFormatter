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
 
class setAttributesTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$node = new SimpleDOM('<node />');

		$node->setAttributes(array(
			'a' => 'aval',
			'b' => 'bval'
		));

		$this->assertXmlStringEqualsXmlString(
			'<node a="aval" b="bval" />',
			$node->asXML()
		);
	}

	public function testNS()
	{
		$node = new SimpleDOM('<node xmlns:ns="urn:ns" />');

		$node->setAttributes(array(
			'a' => 'aval',
			'b' => 'bval'
		), 'urn:ns');

		$this->assertXmlStringEqualsXmlString(
			'<node xmlns:ns="urn:ns" ns:a="aval" ns:b="bval" />',
			$node->asXML()
		);
	}

	public function testExistentAttributesAreOverwritten()
	{
		$node = new SimpleDOM('<node a="old" />');

		$node->setAttributes(array(
			'a' => 'aval',
			'b' => 'bval'
		));

		$this->assertXmlStringEqualsXmlString(
			'<node a="aval" b="bval" />',
			$node->asXML()
		);
	}

	public function testIsChainable()
	{
		$node = new SimpleDOM('<node />');

		$return = $node->setAttributes(array());

		$this->assertEquals($node, $return);
		$this->assertTrue(dom_import_simplexml($node)->isSameNode(dom_import_simplexml($return)));
	}
}