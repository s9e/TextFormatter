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
 
class sortTest extends \PHPUnit_Framework_TestCase
{
	public function testByAttribute()
	{
		$actual = array(
			new SimpleDOM('<child letter="c" />'),
			new SimpleDOM('<child letter="a" />'),
			new SimpleDOM('<child letter="e" />'),
			new SimpleDOM('<child letter="b" />'),
			new SimpleDOM('<child letter="d" />')
		);

		SimpleDOM::sort($actual, '@letter');

		$expected = array(
			new SimpleDOM('<child letter="a" />'),
			new SimpleDOM('<child letter="b" />'),
			new SimpleDOM('<child letter="c" />'),
			new SimpleDOM('<child letter="d" />'),
			new SimpleDOM('<child letter="e" />')
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPointersToNodesAreNotLost()
	{
		$actual = array(
			new SimpleDOM('<child letter="c" />'),
			new SimpleDOM('<child letter="d" />'),
			new SimpleDOM('<child letter="e" />'),
			new SimpleDOM('<child letter="a" />'),
			new SimpleDOM('<child letter="b" />')
		);

		$c = $actual[0];
		$d = $actual[1];
		$e = $actual[2];
		$a = $actual[3];
		$b = $actual[4];

		SimpleDOM::sort($actual, '@letter');

		$a['old_letter'] = 'a';
		$b['old_letter'] = 'b';
		$c['old_letter'] = 'c';
		$d['old_letter'] = 'd';
		$e['old_letter'] = 'e';

		$expected = array(
			new SimpleDOM('<child letter="a" old_letter="a" />'),
			new SimpleDOM('<child letter="b" old_letter="b" />'),
			new SimpleDOM('<child letter="c" old_letter="c" />'),
			new SimpleDOM('<child letter="d" old_letter="d" />'),
			new SimpleDOM('<child letter="e" old_letter="e" />')
		);

		$this->assertEquals($expected, $actual);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testNonSimpleXMLElementAndNonDOMNodeThrowsAnException()
	{
		$actual = array(
			'string'
		);

		SimpleDOM::sort($actual, '@letter');
	}
}