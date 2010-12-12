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
 
class hasClassTest extends \PHPUnit_Framework_TestCase
{
	public function testMatch()
	{
		$node = new SimpleDom('<node class="foo" />');
		$this->assertTrue($node->hasClass('foo'));
	}

	public function testMatchLeadingClass()
	{
		$node = new SimpleDom('<node class="foo bar baz" />');
		$this->assertTrue($node->hasClass('foo'));
	}

	public function testMatchMiddleClass()
	{
		$node = new SimpleDom('<node class="foo bar baz" />');
		$this->assertTrue($node->hasClass('bar'));
	}

	public function testMatchTrailingClass()
	{
		$node = new SimpleDom('<node class="foo bar baz" />');
		$this->assertTrue($node->hasClass('baz'));
	}

	public function testNoSubstringMatch()
	{
		$node = new SimpleDom('<node class="foobar" />');
		$this->assertFalse($node->hasClass('bar'));
	}

	public function testNoCaseInsensitiveMatch()
	{
		$node = new SimpleDom('<node class="Foo" />');
		$this->assertFalse($node->hasClass('foo'));
	}

	public function testNoMatch()
	{
		$node = new SimpleDom('<node class="foo" />');
		$this->assertFalse($node->hasClass('bar'));
	}

	public function testNoMatchNoClass()
	{
		$node = new SimpleDom('<node />');
		$this->assertFalse($node->hasClass('bar'));
	}

	public function testMatchDoesNotAlterTheNode()
	{
		$node     = new SimpleDom('<node class="foo" />');
		$expected = $node->asXML();

		$node->hasClass('foo');

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testNoMatchDoesNotAlterTheNode()
	{
		$node     = new SimpleDom('<node class="foo" />');
		$expected = $node->asXML();

		$node->hasClass('bar');

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testNoMatchNoClassDoesNotAlterTheNode()
	{
		$node     = new SimpleDom('<node />');
		$expected = $node->asXML();

		$node->hasClass('bar');

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}
}