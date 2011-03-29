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
 
class simpledom_load_stringTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$xml = '<root><child1 /><child2 /><child3 /></root>';
		$node = simpledom_load_string($xml);

		$this->assertTrue($node instanceof SimpleDOM, 'Wrong class returned');
		$this->assertXmlStringEqualsXmlString($xml, $node->asXML());
	}
}