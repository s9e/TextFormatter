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
 
class getElementsByClassNameTest extends \PHPUnit_Framework_TestCase
{
	public function testMatch()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="bar" />
				<div class="foo" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('foo');

		$expected = array(
			$node->div[1],
		);

		$this->assertEquals($expected, $actual);
	}

	public function testMultipleMatches()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="bar" />
				<div id="first" class="foo" />
				<div>
					<div id="second" class="foo" />
				</div>
			</node>'
		);

		$actual = $node->getElementsByClassName('foo');

		$expected = array(
			$node->div[1],
			$node->div[2]->div,
		);

		$this->assertEquals($expected, $actual);
	}

	public function testNoMatch()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="bar" />
				<div class="foo" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('baz');

		$expected = array();

		$this->assertEquals($expected, $actual);
	}

	public function testNoSubstringMatch()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="bar" />
				<div class="foobar" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('foo');

		$expected = array();

		$this->assertEquals($expected, $actual);
	}

	public function testMatchLeading()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="quux" />
				<div class="foo bar baz" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('foo');

		$expected = array(
			$node->div[1],
		);

		$this->assertEquals($expected, $actual);
	}

	public function testMatchMiddle()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="quux" />
				<div class="foo bar baz" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('bar');

		$expected = array(
			$node->div[1],
		);

		$this->assertEquals($expected, $actual);
	}

	public function testMatchTrailing()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="quux" />
				<div class="foo bar baz" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('baz');

		$expected = array(
			$node->div[1],
		);

		$this->assertEquals($expected, $actual);
	}

	public function testSingleQuotesReturnNothing()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="quux" />
				<div class="foo bar baz" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName("'foo");

		$expected = array();

		$this->assertEquals($expected, $actual);
	}

	public function testDoubleQuotesReturnNothing()
	{
		$node = new SimpleDOM(
			'<node>
				<div class="quux" />
				<div class="foo bar baz" />
				<div />
			</node>'
		);

		$actual = $node->getElementsByClassName('"foo');

		$expected = array();

		$this->assertEquals($expected, $actual);
	}

	public function testChildContext()
	{
		$node = new SimpleDOM(
			'<node>
				<div id="first" class="foo" />
				<div>
					<div id="second" class="foo" />
				</div>
			</node>'
		);

		$actual = $node->div[1]->getElementsByClassName('foo');

		$expected = array(
			$node->div[1]->div
		);

		$this->assertEquals($expected, $actual);
	}

	public function testContextNodeIsNotReturned()
	{
		$node = new SimpleDOM(
			'<node>
				<div id="first" class="foo">
					<div id="second" class="foo" />
				</div>
			</node>'
		);

		$actual = $node->div->getElementsByClassName('foo');

		$expected = array(
			$node->div->div
		);

		$this->assertEquals($expected, $actual);
	}
}