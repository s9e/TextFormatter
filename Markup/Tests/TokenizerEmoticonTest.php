<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TokenizerEmoticonTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->setEmoticonOption('limit', 10);
		$cb->setEmoticonOption('limit_action', 'ignore');

		$cb->addEmoticon(':)', '<img src="happy.png" alt=":)" />');

		$this->config = $cb->getEmoticonConfig();
	}

	protected function parse($text, $config)
	{
		preg_match_all($config['regexp'], $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
		return Parser::getEmoticonTags($text, $config, $matches);
	}
}