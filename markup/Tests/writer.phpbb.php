<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class test_phpbb_writer extends \PHPUnit_Framework_TestCase
{
	public function testBBCodeWithParam()
	{
		$text     = '[url=http://www.example.com]example.com[/url]';
		$expected =
		            'a:5:{i:0;s:0:"";i:1;a:4:{i:-1;s:28:"[url=http://www.example.com]";i:0;i:1;i:1;s:3:"url";i:2;a:1:{s:1:"_";s:22:"http://www.example.com";}}i:2;s:11:"example.com";i:3;a:3:{i:-1;s:6:"[/url]";i:0;i:3;i:1;s:3:"url";}i:4;s:0:"";}';

		$actual   = $this->parser->parse($text, 's9e\toolkit\markup\phpbb_writer');

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->addBBCode('url', array(
			'default_param'    => '_',
			'content_as_param' => true,
			'default_rule'     => 'deny'
		));
		$cb->addBBCodeParam('url', '_', 'url', true);

		$this->parser = new parser($cb->getParserConfig());
	}
}

class phpbb_writer
{
	protected $ret = array();
	protected $cur;
	protected $bbcodes = array();

	public function openMemory()
	{
		// nothing to do
	}

	public function startElement($name)
	{
		if ($name === 'rt')
		{
			return;
		}

		if (empty($this->ret))
		{
			$this->ret[] = '';
		}

		$this->bbcodes[] = $name = strtolower($name);

		unset($this->cur);
		$this->cur = array(-1 => '', 1, $name, array());
		$this->ret[] =& $this->cur;
	}

	public function writeAttribute($k, $v)
	{
		$this->cur[2][$k] = $v;
	}

	public function writeElement($name, $content = '')
	{
		if ($name === 'st')
		{
			$this->cur[-1] = $content;
		}
		elseif ($name === 'et')
		{
			unset($this->cur);

			$this->cur = array(-1 => $content, 3, null);
			$this->ret[] =& $this->cur;
		}
		else
		{
			throw new \Exception("Didn't see that one coming");
		}
	}

	public function text($text)
	{
		$this->ret[] = $text;
		unset($this->cur);
	}

	public function endElement()
	{
		$this->cur[1] = array_pop($this->bbcodes);
	}

	public function endDocument()
	{
		while (!empty($this->bbcodes))
		{
			$this->writeElement('et');
			$this->endElement();
		}

		if (isset($this->cur))
		{
			$this->ret[] = '';
			unset($this->cur);
		}
	}

	public function outputMemory()
	{
		return serialize($this->ret);
	}
}