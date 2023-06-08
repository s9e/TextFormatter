<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Utils;

use s9e\TextFormatter\Utils\ParsedDOM;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils\ParsedDOM
*/
class ParsedDOMTest extends Test
{
	/**
	* @testdox loadXML() returns an instance of s9e\TextFormatter\Utils\ParsedDOM\Document
	*/
	public function testLoadXML()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Utils\\ParsedDOM\\Document',
			ParsedDOM::loadXML('<r/>')
		);
	}
}