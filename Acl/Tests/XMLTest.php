<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\XMLReader;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../XMLReader.php';

class XMLTest extends \PHPUnit_Framework_TestCase
{
	public function testACLCanBeQueriedInXML()
	{
		$builder = new Builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$builder->allow('foo', array('bar' => 456));
		$builder->deny('foo', array('bar' => 456, 'baz' => 'DENY'));

		$xml = $builder->getReaderXML();

		$reader = new XMLReader($xml);

		$this->assertFalse($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456)));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 456, 'baz' => 'DENY')));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertFalse($reader->isAllowed('zz', array('bar' => 123, 'baz' => 'xyz')));
	}
}