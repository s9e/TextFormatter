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
 
class insertCommentTest extends \PHPUnit_Framework_TestCase
{
	public function testAppendIsDefault()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/><!--TEST--></root>';

		$root->insertComment('TEST');

		$this->assertEqualsWithComments($expected, $root->asXML());
	}

	public function testAppend()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/><!--TEST--></root>';

		$root->insertComment('TEST', 'append');

		$this->assertEqualsWithComments($expected, $root->asXML());
	}

	public function testBefore()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<!--TEST--><root><child1 /><child2 /><child3/></root>';

		$root->insertComment('TEST', 'before');

		$this->assertEqualsWithComments($expected, $root->asXML());
	}

	public function testAfterWithNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><!--TEST--><child2 /><child3/></root>';

		$root->child1->insertComment('TEST', 'after');

		$this->assertEqualsWithComments($expected, $root->asXML());
	}

	public function testAfterWithoutNextSibling()
	{
		$root		= new SimpleDOM('<root><child1 /><child2 /><child3 /></root>');
		$expected	= '<root><child1 /><child2 /><child3/></root><!--TEST-->';

		$root->insertComment('TEST', 'after');

		$this->assertEqualsWithComments($expected, $root->asXML());
	}

	protected function assertEqualsWithComments($expected, $actual)
	{
		$replace = array(
			// remove the XML declaration (LIBXML_NOXMLDECL doesn't seem to work here)
			'#<\\?.*?\\?>#',

			// remove whitespace inside of tags
			'# *(?=/>)#',

			// remove newlines
			'#\\n*#'
		);

		return $this->assertEquals(
			preg_replace($replace, '', $expected),
			preg_replace($replace, '', $actual)
		);
	}
}