<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\OutputHandling;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\OutputHandling
*/
class OutputHandlingTest extends Test
{
	/**
	* @testdox Outputs plain text
	*/
	public function testPlainText()
	{
		$parser = $this->configurator->getParser();

		$this->assertSame(
			'<pt>Plain text</pt>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox Outputs plain text with line breaks
	*/
	public function testPlainTextMultiline()
	{
		$parser = $this->configurator->getParser();

		$this->assertSame(
			"<pt>Plain<br />\ntext</pt>",
			$parser->parse("Plain\ntext")
		);
	}
}

class OutputHandlingDummy extends Parser
{
	public $logger;
	public $pluginParsers = array();
	public $pluginsConfig = array(
		'Test' => array(
		)
	);

	public function __construct($text = '')
	{
		$this->text = $text;
	}

	public function executePluginParsers()
	{
		return call_user_func_array('parent::executePluginParsers', func_get_args());
	}
}