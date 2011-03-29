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
 
class addClassTest extends \PHPUnit_Framework_TestCase
{
	public function testNoClass()
	{
		$node = new SimpleDom('<node class="foo" />');
		$node->addClass('foo');
		$expected = '<node class="foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testEmptyClass()
	{
		$node = new SimpleDom('<node class="" />');
		$node->addClass('foo');
		$expected = '<node class="foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testRedundantClass()
	{
		$node = new SimpleDom('<node class="foo" />');
		$node->addClass('foo');
		$expected = '<node class="foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testDoubleRedundantClass()
	{
		$node = new SimpleDom('<node class="foo foo" />');
		$node->addClass('foo');
		$expected = '<node class="foo foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testWhitespaceIsNotAffected()
	{
		$node = new SimpleDom('<node class="foo  bar" />');
		$node->addClass('baz');
		$expected = '<node class="foo  bar baz" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}
}