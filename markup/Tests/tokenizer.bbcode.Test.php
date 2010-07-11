<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testTokenizerBBCode extends \PHPUnit_Framework_TestCase
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

	public function testContentAsParamWithBBCodeSuffix()
	{
		$ret = parser::getBBCodeTags('[url:1]http://www.example.com/?q[/url]=1[/url:1]', $this->config['bbcode']);

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
			$this->assertSame('http://www.example.com/?q[/url]=1', $ret['tags'][0]['params']['url']);
		}
	}

	public function testTokenizerLimitIsRespected()
	{
		$text = str_repeat('[b]x[/b] ', 6);
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		$this->assertSame(10, count($ret['tags']));
	}

	/**
	* @expectedException Exception
	*/
	public function testTokenizerLimitExceededWithActionAbortThrowsAnException()
	{
		$config = $this->config['bbcode'];
		$config['limit_action'] = 'abort';

		$text = str_repeat('[b]x[/b] ', 6);
		$ret  = parser::getBBCodeTags($text, $config);
	}

	public function testParamInDoubleQuotesIsParsedCorrectly()
	{
		$text = '[x foo="bar"]xxx[/x]';
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		if (!isset($ret['tags'][0]['params']['foo']))
		{
			$this->fail('No param');
		}

		$this->assertSame('bar', $ret['tags'][0]['params']['foo']);
	}

	public function testParamInSingleQuotesIsParsedCorrectly()
	{
		$text = "[x foo='bar']xxx[/x]";
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		if (!isset($ret['tags'][0]['params']['foo']))
		{
			$this->fail('No param');
		}

		$this->assertSame('bar', $ret['tags'][0]['params']['foo']);
	}

	public function testParamWithoutQuotesIsParsedCorrectly()
	{
		$text = '[x foo=bar]xxx[/x]';
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		if (!isset($ret['tags'][0]['params']['foo']))
		{
			$this->fail('No param');
		}

		$this->assertSame('bar', $ret['tags'][0]['params']['foo']);
	}

	public function testEscapedQuotesAreParsedCorrectly()
	{
		$text = '[x foo="\"b\"ar\""]xxx[/x]';
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		if (!isset($ret['tags'][0]['params']['foo']))
		{
			$this->fail('No param');
		}

		$this->assertSame('"b"ar"', $ret['tags'][0]['params']['foo']);
	}

	public function testSelfClosingTagsAreParsedCorrectly()
	{
		$text = '[x/] [x /]';
		$ret  = parser::getBBCodeTags($text, $this->config['bbcode']);

		$this->assertSame(2, count($ret['tags']));
		$this->assertSame('X', $ret['tags'][0]['name']);
		$this->assertSame('X', $ret['tags'][1]['name']);
		$this->assertSame(parser::TAG_SELF, $ret['tags'][0]['type']);
		$this->assertSame(parser::TAG_SELF, $ret['tags'][1]['type']);
		$this->assertSame(4, $ret['tags'][0]['len']);
		$this->assertSame(5, $ret['tags'][1]['len']);
	}

	/**
	* @depends testSelfClosingTagsAreParsedCorrectly
	*/
	public function testSelfClosingTagsCanHaveParams()
	{
		$text     = '[x foo="bar" /]';
		$actual   = parser::getBBCodeTags($text, $this->config['bbcode']);
		$expected = array(
			'tags' => array(
				array(
					'name'   => 'X',
					'pos'    => 0,
					'len'    => 15,
					'params' => array('foo' => 'bar')
				)
			)
		);

		$this->assertKindaEquals($expected, $actual);
	}

	/**
	* @testSelfClosingTagsCanHaveParams
	*/
	public function testQuotesCanBeEscapedInsideParamValues()
	{
		$text     = '[x foo="ba\\"r" /]';
		$actual   = parser::getBBCodeTags($text, $this->config['bbcode']);
		$expected = array(
			'tags' => array(
				array(
					'name'   => 'X',
					'pos'    => 0,
					'len'    => 17,
					'params' => array('foo' => 'ba"r')
				)
			)
		);

		$this->assertKindaEquals($expected, $actual);
	}

	/**
	* @testSelfClosingTagsCanHaveParams
	*/
	public function testBackslashesAndQuotesCanBeEscapedInsideParamValues()
	{
		// foo="ba\\\"r" -- that's one escaped backslash followed by one escaped quote
		$text     = '[x foo="ba\\\\\\"r" /]';
		$actual   = parser::getBBCodeTags($text, $this->config['bbcode']);
		$expected = array(
			'tags' => array(
				array(
					'name'   => 'X',
					'pos'    => 0,
					'len'    => 19,
					'params' => array('foo' => 'ba\\"r')
				)
			)
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testUnterminatedParamValueGeneratesAnError()
	{
		$text     = '[x foo=" /]';
		$actual   = parser::getBBCodeTags($text, $this->config['bbcode']);
		$expected = array(
			'tags' => array(),
			'msgs' => array(
				'error' => array(
					array('pos' => 7)
				)
			)
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->setBBCodeOption('limit', 10);
		$cb->setBBCodeOption('limit_action', 'ignore');

		$cb->addBBCode('b');
		$cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));
		$cb->addBBCode('x');
		$cb->addBBCode('y');

		$cb->addBBCodeParam('x', 'foo', 'text', false);
		$cb->addBBCodeParam('y', 'foo', 'text', true);

		$cb->addBBCodeParam('url', 'url', 'url', true);

		$this->config = $cb->getParserConfig();
		$this->parser = new parser($this->config);
	}

	protected function assertKindaEquals($expected, $actual)
	{
		foreach ($expected as $type => $content)
		{
			$this->assertArrayHasKey($type, $actual);
			$this->assertSame(count($expected[$type]), count($actual[$type]), "Wrong $type count");

			switch ($type)
			{
				case 'msgs':
					$this->assertKindaEquals($expected['msgs'], $actual['msgs']);
					break;

				case 'tags':
				case 'error':
				case 'warning':
				case 'debug':
					foreach ($content as $k => $v)
					{
						$this->assertEquals(
							$v,
							array_intersect_key($actual[$type][$k], $v)
						);
					}
					break;

				default:
					$this->fail('Unknown key');
			}
		}
	}
}