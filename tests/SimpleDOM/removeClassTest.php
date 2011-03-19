<?php
/*

Copyright 2009-2010 The SimpleDOM authors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class removeClassTest extends \PHPUnit_Framework_TestCase
{
	public function testMethodIsChainable()
	{
		$node = new SimpleDom('<node />');

		$this->assertSame($node, $node->removeClass('foo'));
	}

	public function testNoClass()
	{
		$node = new SimpleDom('<node />');
		$node->removeClass('foo');
		$expected = '<node />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testEmptyClass()
	{
		$node = new SimpleDom('<node class="" />');
		$node->removeClass('foo');
		$expected = '<node class="" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testMatch()
	{
		$node = new SimpleDom('<node class="foo" />');
		$node->removeClass('foo');
		$expected = '<node class="" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testNoMatch()
	{
		$node = new SimpleDom('<node class="foo" />');
		$node->removeClass('bar');
		$expected = '<node class="foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testNoSubstringMatch()
	{
		$node = new SimpleDom('<node class="foobar" />');
		$node->removeClass('bar');
		$expected = '<node class="foobar" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testNoCaseInsensitiveMatch()
	{
		$node = new SimpleDom('<node class="Foo" />');
		$node->removeClass('foo');
		$expected = '<node class="Foo" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}

	public function testRedundantClassesAreRemoved()
	{
		$node = new SimpleDom('<node class="foo foo bar" />');
		$node->removeClass('foo');
		$expected = '<node class="bar" />';

		$this->assertXmlStringEqualsXmlString($expected, $node->asXML());
	}
}