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
 
class sortedXPathTest extends \PHPUnit_Framework_TestCase
{
	public function testByAttribute()
	{
		$node = new SimpleDOM(
			'<node>
				<child letter="a" />
				<child letter="d" />
				<child letter="b" />
				<child letter="c" />
				<child letter="e" />
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child letter="a" />'),
			new SimpleDOM('<child letter="b" />'),
			new SimpleDOM('<child letter="c" />'),
			new SimpleDOM('<child letter="d" />'),
			new SimpleDOM('<child letter="e" />')
		);

		$actual = $node->sortedXPath('//child', '@letter');

		$this->assertEquals($expected, $actual);
	}

	public function testByNumericAttribute()
	{
		$node = new SimpleDOM(
			'<node>
				<child number="3" />
				<child number="11" />
				<child number="1" />
				<child number="2" />
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child number="1" />'),
			new SimpleDOM('<child number="2" />'),
			new SimpleDOM('<child number="3" />'),
			new SimpleDOM('<child number="11" />')
		);

		$actual = $node->sortedXPath('//child', '@number', SORT_NUMERIC);
		$this->assertEquals($expected, $actual);
	}

	public function testByStringAttribute()
	{
		$node = new SimpleDOM(
			'<node>
				<child number="3" />
				<child number="11" />
				<child number="1" />
				<child number="2" />
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child number="1" />'),
			new SimpleDOM('<child number="11" />'),
			new SimpleDOM('<child number="2" />'),
			new SimpleDOM('<child number="3" />')
		);

		$actual = $node->sortedXPath('//child', '@number', SORT_STRING);
		$this->assertEquals($expected, $actual);
	}

	public function testByMultipleAttributes()
	{
		$node = new SimpleDOM(
			'<node>
				<child letter="e" number="2" />
				<child letter="d" number="3" />
				<child letter="b" number="1" />
				<child letter="c" number="1" />
				<child letter="a" number="2" />
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child letter="b" number="1" />'),
			new SimpleDOM('<child letter="c" number="1" />'),
			new SimpleDOM('<child letter="a" number="2" />'),
			new SimpleDOM('<child letter="e" number="2" />'),
			new SimpleDOM('<child letter="d" number="3" />')
		);

		$actual = $node->sortedXPath('//child', '@number', '@letter');
		$this->assertEquals($expected, $actual);
	}

	public function testByMultipleAttributesDifferentOrders()
	{
		$node = new SimpleDOM(
			'<node>
				<child letter="e" number="2" />
				<child letter="d" number="3" />
				<child letter="b" number="1" />
				<child letter="c" number="1" />
				<child letter="a" number="2" />
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child letter="d" number="3" />'),
			new SimpleDOM('<child letter="a" number="2" />'),
			new SimpleDOM('<child letter="e" number="2" />'),
			new SimpleDOM('<child letter="b" number="1" />'),
			new SimpleDOM('<child letter="c" number="1" />')
		);

		$actual = $node->sortedXPath('//child', '@number', SORT_DESC, '@letter');
		$this->assertEquals($expected, $actual);
	}

	public function testByChild()
	{
		$node = new SimpleDOM(
			'<node>
				<child><letter>e</letter></child>
				<child><letter>b</letter></child>
				<child><letter>c</letter></child>
				<child><letter>d</letter></child>
				<child><letter>a</letter></child>
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child><letter>a</letter></child>'),
			new SimpleDOM('<child><letter>b</letter></child>'),
			new SimpleDOM('<child><letter>c</letter></child>'),
			new SimpleDOM('<child><letter>d</letter></child>'),
			new SimpleDOM('<child><letter>e</letter></child>')
		);

		$actual = $node->sortedXPath('//child', 'letter');

		$this->assertEquals($expected, $actual);
	}

	public function testByMissingChild()
	{
		$node = new SimpleDOM(
			'<node>
				<child><letter>e</letter></child>
				<child><letter>b</letter></child>
				<child />
				<child><letter>c</letter></child>
				<child><letter>d</letter></child>
				<child><letter>a</letter></child>
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child />'),
			new SimpleDOM('<child><letter>a</letter></child>'),
			new SimpleDOM('<child><letter>b</letter></child>'),
			new SimpleDOM('<child><letter>c</letter></child>'),
			new SimpleDOM('<child><letter>d</letter></child>'),
			new SimpleDOM('<child><letter>e</letter></child>')
		);

		$actual = $node->sortedXPath('//child', 'letter');

		$this->assertEquals($expected, $actual);
	}

	public function testSortByDot()
	{
		$node = new SimpleDOM(
			'<node>
				<child><letter>e</letter></child>
				<child><letter>b</letter></child>
				<child><letter>c</letter></child>
				<child><letter>d</letter></child>
				<child><letter>a</letter></child>
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child><letter>a</letter></child>'),
			new SimpleDOM('<child><letter>b</letter></child>'),
			new SimpleDOM('<child><letter>c</letter></child>'),
			new SimpleDOM('<child><letter>d</letter></child>'),
			new SimpleDOM('<child><letter>e</letter></child>')
		);

		$actual = $node->sortedXPath('//child', '.');

		$this->assertEquals($expected, $actual);
	}

	public function testSortByExpr()
	{
		$node = new SimpleDOM(
			'<node>
				<child>2</child>
				<child>1</child>
				<child>4</child>
				<child>3</child>
			</node>'
		);

		$expected = array(
			new SimpleDOM('<child>4</child>'),
			new SimpleDOM('<child>3</child>'),
			new SimpleDOM('<child>2</child>'),
			new SimpleDOM('<child>1</child>')
		);

		$actual = $node->sortedXPath('//child', '4 - number(.)');

		$this->assertEquals($expected, $actual);
	}
}