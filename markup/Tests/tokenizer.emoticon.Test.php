<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testTokenizerEmoticon extends \PHPUnit_Framework_TestCase
{
	public function testTokenizerLimitIsRespected()
	{
		$text = str_repeat(':)', 11);
		$ret  = parser::getEmoticonTags($text, $this->config['emoticon']);

		$this->assertSame(10, count($ret['tags']));
	}

	/**
	* @expectedException Exception
	*/
	public function testTokenizerLimitExceededWithActionAbortThrowsAnException()
	{
		$config = $this->config['emoticon'];
		$config['limit_action'] = 'abort';

		$text = str_repeat(':)', 11);
		$ret  = parser::getEmoticonTags($text, $config);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->setEmoticonOption('limit', 10);
		$cb->setEmoticonOption('limit_action', 'ignore');

		$cb->addBBCode('e', array(
			'default_param'    => 'code',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('e', 'code', 'string', true);

		$cb->setEmoticonOption('bbcode', 'e');
		$cb->setEmoticonOption('param', 'code');

		$cb->addEmoticon(':)');

		$this->config = $cb->getParserConfig();
		$this->parser = new parser($this->config);
	}
}