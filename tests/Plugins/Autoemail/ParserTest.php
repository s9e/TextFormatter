<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoemail;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autoemail\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavascriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoemail\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavascriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'Hit me at example@example.com',
				'<rt>Hit me at <EMAIL email="example@example.com">example@example.com</EMAIL></rt>'
			),
			array(
				'Hit me at example@example.com',
				'<rt>Hit me at <FOO email="example@example.com">example@example.com</FOO></rt>',
				array('tagName' => 'FOO')
			),
			array(
				'Hit me at example@example.com',
				'<rt>Hit me at <EMAIL bar="example@example.com">example@example.com</EMAIL></rt>',
				array('attrName' => 'bar')
			),
			array(
				'Twit me at @foo.bar',
				'<pt>Twit me at @foo.bar</pt>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>'
			),
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				array('tagName' => 'FOO')
			),
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				array('tagName' => 'FOO')
			),
		);
	}
}