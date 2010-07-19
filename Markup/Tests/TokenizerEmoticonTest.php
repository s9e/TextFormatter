<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TokenizerEmoticonTest extends \PHPUnit_Framework_TestCase
{
	public function testTokenizerLimitIsRespected()
	{
		$text = str_repeat(':)', 11);
		$ret  = Parser::getEmoticonTags($text, $this->config);

		$this->assertSame(10, count($ret['tags']));
	}

	/**
	* @expectedException Exception
	*/
	public function testTokenizerLimitExceededWithActionAbortThrowsAnException()
	{
		$config = $this->config;
		$config['limit_action'] = 'abort';

		$text = str_repeat(':)', 11);
		$ret  = Parser::getEmoticonTags($text, $config);
	}

	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->setEmoticonOption('limit', 10);
		$cb->setEmoticonOption('limit_action', 'ignore');

		$cb->addEmoticon(':)', '<img src="happy.png" alt=":)" />');

		$this->config = $cb->getEmoticonConfig();
	}
}