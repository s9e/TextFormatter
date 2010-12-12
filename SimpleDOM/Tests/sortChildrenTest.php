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
 
class sortChildrenTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$node = new SimpleDOM(
			'<node>
				<child letter="c" />
				<child letter="d" />
				<child letter="e" />
				<child letter="a" />
				<child letter="b" />
			</node>'
		);

		$node->sortChildren('@letter');

		$expected = 
			'<node>
				<child letter="a" />
				<child letter="b" />
				<child letter="c" />
				<child letter="d" />
				<child letter="e" />
			</node>';

		$this->assertXmlStringEqualsXmlString(
			$expected,
			$node->asXML()
		);
	}

	public function testPointersToNodesAreNotLost()
	{
		$node = new SimpleDOM(
			'<node>
				<child letter="c" />
				<child letter="d" />
				<child letter="e" />
				<child letter="a" />
				<child letter="b" />
			</node>'
		);

		$c = $node->child[0];
		$d = $node->child[1];
		$e = $node->child[2];
		$a = $node->child[3];
		$b = $node->child[4];

		$node->sortChildren('@letter');

		$a['old_letter'] = 'a';
		$b['old_letter'] = 'b';
		$c['old_letter'] = 'c';
		$d['old_letter'] = 'd';
		$e['old_letter'] = 'e';

		$expected = 
			'<node>
				<child letter="a" old_letter="a" />
				<child letter="b" old_letter="b" />
				<child letter="c" old_letter="c" />
				<child letter="d" old_letter="d" />
				<child letter="e" old_letter="e" />
			</node>';

		$this->assertXmlStringEqualsXmlString(
			$expected,
			$node->asXML()
		);
	}
}