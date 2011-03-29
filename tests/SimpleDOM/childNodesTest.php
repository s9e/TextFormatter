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
 
class childNodesTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$root = new SimpleDOM(
			'<root>
				<child1 />
				<child2 />
				<child3>
					<grandchild />
				</child3>
			</root>',

			LIBXML_NOBLANKS
		);

		$expected_return = array(
			new SimpleDOM('<child1 />'),
			new SimpleDOM('<child2 />'),
			new SimpleDOM('<child3><grandchild /></child3>'),
		);

		$return = $root->childNodes();

		$this->assertEquals($expected_return, $return);
	}

	public function testTextNodes()
	{
		$root = new SimpleDOM(
			'<root>Some <b>bold</b> text</root>'
		);

		$expected_return = array(
			'Some ',
			new SimpleDOM('<b>bold</b>'),
			' text'
		);

		$return = $root->childNodes();

		$this->assertEquals($expected_return, $return);
	}
}