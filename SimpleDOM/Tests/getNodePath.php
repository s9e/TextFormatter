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
 
class getNodePathTest extends \PHPUnit_Framework_TestCase
{
	public function testOnlyElement()
	{
		$div = new SimpleDOM('<div><b>first</b></div>');

		$this->assertSame('/div/b', $div->b->getNodePath());
	}

	public function testFirstOfTwoElements()
	{
		$div = new SimpleDOM('<div><b>first</b> and <b>second</b></div>');

		$this->assertSame('/div/b[1]', $div->b->getNodePath());
	}

	public function testSecondOfTwoElements()
	{
		$div = new SimpleDOM('<div><b>first</b> and <b>second</b></div>');

		$this->assertSame('/div/b[2]', $div->b[1]->getNodePath());
	}
}