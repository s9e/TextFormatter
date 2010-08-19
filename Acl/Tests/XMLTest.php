<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Resource;
use s9e\Toolkit\Acl\XMLReader;

include_once __DIR__ . '/../Acl.php';
include_once __DIR__ . '/../XMLReader.php';

class XMLTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('foo', array('bar' => 456));
		$acl->deny('foo', array('bar' => 456, 'baz' => 'DENY'));

		$xml = $acl->getReaderXML();

		$this->reader = new XMLReader($xml);
	}

	public function testACLCanBeQueriedInXML()
	{
		$this->assertFalse($this->reader->isAllowed('foo'));
		$this->assertTrue($this->reader->isAllowed('foo', array('bar' => 456)));
		$this->assertFalse($this->reader->isAllowed('foo', array('bar' => 456, 'baz' => 'DENY')));
		$this->assertFalse($this->reader->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($this->reader->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertFalse($this->reader->isAllowed('zz', array('bar' => 123, 'baz' => 'xyz')));
	}

	public function testXMLReaderSupportsAnyAsScopeValue()
	{
		$this->assertTrue($this->reader->isAllowed('foo', array('bar' => $this->reader->any())));
		$this->assertFalse($this->reader->isAllowed('foo', array('baz' => $this->reader->any())));
		$this->assertTrue($this->reader->isAllowed('foo', array('bar' => $this->reader->any(), 'baz' => $this->reader->any())));
	}

	public function testXMLReaderSupportsAnyAsScope()
	{
		$this->assertTrue($this->reader->isAllowed('foo', $this->reader->any()));
	}

	public function testXMLReaderSupportsResourcesAsScope()
	{
		$this->assertFalse($this->reader->isAllowed('foo'));
		$this->assertTrue($this->reader->isAllowed('foo', new MyResource));
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testXMLReaderRejectsInvalidScopes()
	{
		$this->reader->isAllowed('foo', 1);
	}
}

class MyResource implements Resource
{
	public function getAclBuilderScope(){}
	public function getAclReaderScope()
	{
		return array('bar' => 123, 'baz' => 'xyz');
	}
}