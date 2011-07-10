<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\GenericConfig
*/
class GenericConfigTest extends Test
{
	/**
	* @testdox getConfig() returns false if no replacements were added
	*/
	public function testReturnsFalseIfNoReplacements()
	{
		$this->assertFalse($this->cb->loadPlugin('Generic')->getConfig());
	}

	/**
	* @testdox getConfig() creates a regexp for each replacement
	*/
	public function testRegexpsAreCreatedForEachReplacement()
	{
		$this->cb->Generic->addReplacement('#a#', '<b>a</b>');
		$this->cb->Generic->addReplacement('#b#', '<b>b</b>');

		$config = $this->cb->Generic->getConfig();

		$this->assertArrayHasKey('regexp', $config);
		$this->assertSame(2, count($config['regexp']));
	}

	/**
	* @testdox addReplacement() returns the name of the tag created
	*/
	public function testTagNameIsReturned()
	{
		$tagName = $this->cb->Generic->addReplacement('#a#', '<b>a</b>');

		$this->assertTrue($this->cb->tagExists($tagName));
	}

	/**
	* @testdox addReplacement() accepts subpatterns using the (?P<name>) syntax
	*/
	public function testPythonStyleRegexp()
	{
		$tagName = $this->cb->Generic->addReplacement('#(?P<xy>(?P<zz>a))#', '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @testdox addReplacement() accepts subpatterns using the (?<name>) syntax
	*/
	public function testPerlBracketsStyleRegexp()
	{
		$tagName = $this->cb->Generic->addReplacement('#(?<xy>(?<zz>a))#', '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @testdox addReplacement() accepts subpatterns using the (?'name') syntax
	*/
	public function testPerlQuotesStyleRegexp()
	{
		$tagName = $this->cb->Generic->addReplacement("#(?'xy'(?'zz'a))#", '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @testdox addReplacement() throws an exception if the regexp is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regexp
	*/
	public function testInvalidRegexp()
	{
		$this->cb->Generic->addReplacement('invalid', '<b>a</b>');
	}

	/**
	* @testdox addReplacement() throws a RuntimeException on duplicate named subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Duplicate named subpatterns are not allowed
	*/
	public function testDuplicateSubpatterns()
	{
		$this->cb->Generic->addReplacement('#(?J)(?<foo>x)(?<foo>z)#', '<b>a</b>');
	}

	/**
	* @testdox addReplacement() creates a regexp for every attribute
	* @depends testPerlBracketsStyleRegexp
	*/
	public function testAttributesRegexps()
	{
		$tagName = $this->cb->Generic->addReplacement('#(?<xy>(?<zz>a))#', '<b>a</b>');

		$this->assertSame(
			'#^(?<xy>(?<zz>a))$#D',
			$this->cb->getTagAttributeOption($tagName, 'xy', 'regexp')
		);

		$this->assertSame(
			'#^(?<zz>a)$#D',
			$this->cb->getTagAttributeOption($tagName, 'zz', 'regexp')
		);
	}

	/**
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function test_getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/TextFormatter/Plugins/GenericParser.js',
			$this->cb->Generic->getJSParser()
		);
	}
}