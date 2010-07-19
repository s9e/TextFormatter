<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TokenizerAutolinkTest extends \PHPUnit_Framework_TestCase
{
	public function testTokenizerLimitIsRespected()
	{
		$text = str_repeat('http://example.com ', 11);
		$ret  = parser::getAutolinkTags($text, $this->config);

		// each link is between 2 tags
		$this->assertSame(20, count($ret['tags']));
	}

	/**
	* @expectedException Exception
	*/
	public function testTokenizerLimitExceededWithActionAbortThrowsAnException()
	{
		$config = $this->config;
		$config['limit_action'] = 'abort';

		$text = str_repeat('http://example.com ', 11);
		$ret  = parser::getAutolinkTags($text, $config);
	}

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
}