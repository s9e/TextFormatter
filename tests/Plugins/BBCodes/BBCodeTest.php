<?php

namespace s9e\TextFormatter\Tests\Configurator\Plugins\BBCodes;

use s9e\TextFormatter\Plugins\BBCodes\BBCode;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\BBCode
*/
class BBCodeTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$bbcode = new BBCode(array('tagName' => 'URL'));
		$this->assertSame('URL', $bbcode->tagName);
	}

	/**
	* @testdox BBCode::normalizeName('*') returns '*'
	*/
	public function testNormalizeNameAsterisk()
	{
		$this->assertSame('*', BBCode::normalizeName('*'));
	}

	/**
	* @testdox BBCode::normalizeName('foo') returns 'FOO'
	*/
	public function testNormalizeNameValid()
	{
		$this->assertSame('FOO', BBCode::normalizeName('foo'));
	}

	/**
	* @testdox BBCode::normalizeName('*invalid*') throws an exception
	* @expectedException Exception
	*/
	public function testNormalizeNameInvalid()
	{
		BBCode::normalizeName('*invalid*');
	}

	/**
	* @testdox defaultAttribute accepts an attribute name and normalizes it accordingly
	*/
	public function testDefaultAttribute()
	{
		$bbcode = new BBCode;
		$bbcode->defaultAttribute = 'uRl';

		$this->assertSame('url', $bbcode->defaultAttribute);
	}

	/**
	* @testdox tagName accepts a tag name and normalizes it accordingly
	*/
	public function testTagName()
	{
		$bbcode = new BBCode;
		$bbcode->tagName = 'uRl';

		$this->assertSame('URL', $bbcode->tagName);
	}
}