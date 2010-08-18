<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\XMLReader;

include_once __DIR__ . '/../Acl.php';
include_once __DIR__ . '/../XMLReader.php';

class XMLTest extends \PHPUnit_Framework_TestCase
{
	public function testACLCanBeQueriedInXML()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('foo', array('bar' => 456));
		$acl->deny('foo', array('bar' => 456, 'baz' => 'DENY'));

		$xml = $acl->getReaderXML();

		$reader = new XMLReader($xml);

		$this->assertFalse($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456)));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 456, 'baz' => 'DENY')));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertFalse($reader->isAllowed('zz', array('bar' => 123, 'baz' => 'xyz')));
	}
}