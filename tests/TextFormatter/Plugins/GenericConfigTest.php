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
}