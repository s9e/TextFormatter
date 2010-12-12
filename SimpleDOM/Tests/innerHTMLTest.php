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
 
class innerHTMLTest extends \PHPUnit_Framework_TestCase
{
	public function testElementsArePreserved()
	{
		$div = new SimpleDOM('<div>This is a <b>bold</b> text</div>');

		$this->assertSame(
			'This is a <b>bold</b> text',
			$div->innerHTML()
		);
	}

	public function testHTMLEntitiesAreResolved()
	{
		$div = new SimpleDOM('<div>This is an &amp;ampersand</div>');

		$this->assertSame(
			'This is an &ampersand',
			$div->innerHTML()
		);
	}

	public function testHTMLNumericEntitiesAreResolved()
	{
		$div = new SimpleDOM('<div>This is &#97;&#x6E; a and a n</div>');

		$this->assertSame(
			'This is an a and a n',
			$div->innerHTML()
		);
	}

	public function testCDATASectionsAreResolved()
	{
		$div = new SimpleDOM('<div>This is a <![CDATA[<CDATA>]]> section</div>');

		$this->assertSame(
			'This is a <CDATA> section',
			$div->innerHTML()
		);
	}
}