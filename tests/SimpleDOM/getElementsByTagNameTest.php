<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class getElementsByTagNameTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$root = new SimpleDOM(
			'<root>
				<tag id="foo" />
				<othertag>
					<tag id="bar" />
				</othertag>
				<tag id="baz" />
			</root>'
		);

		$expected_return = array(
			clone $root->tag[0],
			clone $root->othertag->tag,
			clone $root->tag[1]
		);

		$return = $root->getElementsByTagName('tag');

		$this->assertEquals($expected_return, $return);
	}

	public function testPrefix()
	{
		$root = new SimpleDOM(
			'<root xmlns:xxx="urn:xxx">
				<xxx:tag id="foo" />
				<othertag>
					<xxx:tag id="bar" />
				</othertag>
				<tag id="baz" />
			</root>'
		);

		$expected_return = array(
//			new SimpleDOM('<xxx:tag id="foo" xmlns:xxx="urn:xxx" />'),
//			new SimpleDOM('<xxx:tag id="bar" xmlns:xxx="urn:xxx" />')
		);

		$return = $root->getElementsByTagName('xxx:tag');

		$this->assertEquals($expected_return, $return);
	}

	public function testNotFound()
	{
		$root = new SimpleDOM('<root />');

		$expected_return = array();
		$return = $root->getElementsByTagName('inexistent');

		$this->assertEquals($expected_return, $return);
	}
}