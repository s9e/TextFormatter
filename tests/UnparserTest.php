<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\Unparser;

include_once __DIR__ . '/../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Unparser
*/
class UnparserTest extends Test
{
	protected function assertUnparse($text)
	{
		$this->assertSame(
			$text,
			Unparser::unparse($this->parser->parse($text))
		);
	}

	public function testCanUnparsePlainText()
	{
		$this->assertUnparse('Plain text');
	}

	public function testCanUnparsePlainTextWithSpecialChars()
	{
		$this->assertUnparse('<"Plain text">');
	}

	public function testCanUnparseRichText()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img/>');
		$this->assertUnparse('Rich text :)');
	}
}