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
 
class outerXMLTest extends \PHPUnit_Framework_TestCase
{
	public function testNoProlog()
	{
		$div = new SimpleDOM('<div>This is a text</div>');

		$this->assertSame(
			'<div>This is a text</div>',
			$div->outerXML()
		);
	}

	public function testChild()
	{
		$node = new SimpleDOM(
			'<node>
				<child>This is child 0</child>
				<child>This is child 1</child>
				<child>This is child 2</child>
			</node>');

		$this->assertSame(
			'<child>This is child 1</child>',
			$node->child[1]->outerXML()
		);
	}
}