<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode
*/
class BBCodeTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$bbcode = new BBCode(['tagName' => 'URL']);
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
	*/
	public function testNormalizeNameInvalid()
	{
		$this->expectException('Exception');
		$this->expectExceptionMessage('Invalid BBCode name');

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

	/**
	* @testdox $bbcode->contentAttributes is an instance of AttributeList
	*/
	public function testContentAttributesInstance()
	{
		$bbcode = new BBCode;
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\AttributeList',
			$bbcode->contentAttributes
		);
	}

	/**
	* @testdox asConfig() returns its set properties
	*/
	public function testAsConfig()
	{
		$bbcode = new BBCode;
		$bbcode->forceLookahead = true;
		$bbcode->tagName = 'FOO';

		$this->assertSame(
			['forceLookahead' => true, 'tagName' => 'FOO'],
			$bbcode->asConfig()
		);
	}

	/**
	* @testdox asConfig() omits forceLookahead is it's FALSE
	*/
	public function testAsConfigForceLookaheadFalse()
	{
		$bbcode = new BBCode;
		$bbcode->forceLookahead = false;
		$bbcode->tagName = 'FOO';

		$this->assertSame(
			['tagName' => 'FOO'],
			$bbcode->asConfig()
		);
	}
}