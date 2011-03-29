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
 
class innerXMLTest extends \PHPUnit_Framework_TestCase
{
	protected function assertContentIsSame($content)
	{
		$node = new SimpleDOM('<node>' . $content . '</node>', LIBXML_NOENT);
		$this->assertSame($content, $node->innerXML());
	}

	public function testElementsArePreserved()
	{
		$this->assertContentIsSame('This is a <b>bold</b>');
	}

	public function testHTMLEntitiesArePreserved()
	{
		$this->assertContentIsSame('This is an &amp;ampersand');
	}

	public function testCDATASectionsArePreserved()
	{
		$this->assertContentIsSame('This is a <![CDATA[<CDATA>]]> section');
	}
}