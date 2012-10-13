<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use s9e\TextFormatter\Plugins\BBCodes\BBCode;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\BBCode
*/
class BBCodeTest extends Test
{
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
}