<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TokenizerAutolinkTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->setAutolinkOption('limit', 10);
		$cb->setAutolinkOption('limit_action', 'ignore');

		$cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('url', 'url', 'url', true);

		$cb->setAutolinkOption('bbcode', 'url');
		$cb->setAutolinkOption('param', 'url');

		$this->config = $cb->getAutolinkConfig();
	}

	protected function parse($text, $config)
	{
		preg_match_all($config['regexp'], $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
		return Parser::getAutolinkTags($text, $config, $matches);
	}
}