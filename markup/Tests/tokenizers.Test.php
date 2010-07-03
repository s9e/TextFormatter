<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testTokenizers extends \PHPUnit_Framework_TestCase
{
	public function testContentAsParam()
	{
		$ret = parser::getBBCodeTags('[url]http://www.example.com[/url]', $this->config['bbcode']);

		if (empty($ret['tags']))
		{
			$this->fail('No tags were parsed');
		}
		elseif (!isset($ret['tags'][0]['params']['url']))
		{
			$this->fail('The "url" param is missing');
		}
		else
		{
			$this->assertSame('http://www.example.com', $ret['tags'][0]['params']['url']);
		}
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->addBBCode('b');
		$cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));

		$cb->addBBCodeParam('url', 'url', 'url', true);

		$this->config = $cb->getParserConfig();
		$this->parser = new parser($this->config);
	}
}