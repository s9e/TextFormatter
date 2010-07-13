<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testTokenizerEmoticon extends \PHPUnit_Framework_TestCase
{
	public function testTokenizerLimitIsRespected()
	{
		$text = str_repeat(':)', 11);
		$ret  = parser::getEmoticonTags($text, $this->config);

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
		$ret  = parser::getEmoticonTags($text, $config);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->setEmoticonOption('limit', 10);
		$cb->setEmoticonOption('limit_action', 'ignore');

		$cb->addEmoticon(':)', '<img src="happy.png" alt=":)" />');

		$this->config = $cb->getEmoticonConfig();
	}
}