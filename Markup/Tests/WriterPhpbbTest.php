<?php

namespace s9e\Toolkit\Markup\Tests;

use s9e\Toolkit\Markup\ConfigBuilder,
	s9e\Toolkit\Markup\Parser;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class WriterPhpbbTest extends \PHPUnit_Framework_TestCase
{
	public function testBBCodeWithParam()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('url', array(
			'default_param'    => '_',
			'content_as_param' => true,
			'default_rule'     => 'deny'
		));
		$cb->addBBCodeParam('url', '_', 'url', true);

		$text     = '[url=http://www.example.com]example.com[/url]';
		$expected =
		            'a:5:{i:0;s:0:"";i:1;a:4:{i:-1;s:28:"[url=http://www.example.com]";i:0;i:1;i:1;s:3:"url";i:2;a:1:{s:1:"_";s:22:"http://www.example.com";}}i:2;s:11:"example.com";i:3;a:3:{i:-1;s:6:"[/url]";i:0;i:3;i:1;s:3:"url";}i:4;s:0:"";}';

		$actual   = $cb->getParser()->parse($text, __NAMESPACE__ . '\\phpbb_writer');

		$this->assertSame($expected, $actual);
	}

	public function test_phpBB3()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('quote', array(
			'default_param' => 'author',
			'nesting_limit' => 10
		));
		$cb->addBBCodeParam('quote', 'author', 'text', false);

		$cb->addBBCode('b');

		$cb->addBBCode('i');

		$cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('url', 'url', 'url', true);

		$cb->addBBCode('img', array(
			'default_param'    => 'src',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('img', 'src', 'url', true);

		$cb->addBBCode('size', array(
			'default_param' => 'size'
		));
		$cb->addBBCodeParam('size', 'size', 'font-size', true);
		$cb->setFilter('font-size', array(__NAMESPACE__ . '\\phpbb3_writer', 'filterFontSize'), array(
			'min' => 7,
			'max' => 20
		));

		$cb->addBBCode('color', array(
			'default_param' => 'color'
		));
		$cb->addBBCodeParam('color', 'color', 'color', true);

		$cb->addBBCode('u');

		$cb->addBBCode('code', array(
			'default_param' => 'lang'
		));
		$cb->addBBCodeParam('code', 'lang', 'simpletext', false);

		$cb->addBBCode('list', array(
			'default_param' => 'type'
		));
		$cb->addBBCodeParam('list', 'type', 'simpletext', false);

		$cb->addBBCode('li');
		$cb->addBBCodeAlias('li', '*');
		$cb->addBBCodeRule('li', 'require_parent', 'list');
		$cb->addBBCodeRule('li', 'close_parent', 'li');

		$cb->addBBCode('email', array(
			'default_param'    => 'email',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('email', 'email', 'email', true);

		//======================================================================
		$cb->addEmoticon(':)', '');
		phpbb3_writer::$smilies[':)'] = '<!-- s:) --><img src="{SMILIES_PATH}/icon_e_smile.gif" alt=":)" title="Smile" /><!-- s:) -->';

		$cb->addEmoticon(':lol:', '');
		phpbb3_writer::$smilies[':lol:'] = '<!-- s:lol: --><img src="{SMILIES_PATH}/icon_lol.gif" alt=":lol:" title="Laughing" /><!-- s:lol: -->';

		$cb->addBBCode('smiley', array('internal_use' => true));
		$cb->setEmoticonOption('bbcode', 'smiley');
		//======================================================================

		$config = $cb->getParserConfig();
		$parser = new Parser($config);

		phpbb3_writer::$uid = '012345678';

		$text     = "[list][*][quote]\n[*][quote]test[/quote][/quote]:)[/list]";
		$actual   = $parser->parse($text, __NAMESPACE__ . '\\phpbb3_writer');
		$expected =
			"[list:012345678][*:012345678][quote:012345678]\n[*][quote:012345678]test[/quote:012345678][/quote:012345678]<!-- s:) -->".'<img src="{SMILIES_PATH}/icon_e_smile.gif" alt=":)" title="Smile" /><!-- s:) -->[/*:m:012345678][/list:012345678]';

		$this->assertSame($expected, $actual);
	}
}

class phpbb3_writer
{
	static public $uid;
	static public $smilies = array();

	protected $ret = '';
	protected $tag_closed = false;

	public function openMemory()
	{
		if (!isset(self::$uid))
		{
			self::$uid = base_convert(
				mt_rand(
					base_convert('10000000', 36, 10),
					base_convert('zzzzzzzz', 36, 10)
				),
				10,
				36
			);
		}
	}

	public function startElement($name)
	{
		if ($name === 'rt')
		{
			return;
		}

		if ($name === 'LI')
		{
			$name = '*';
		}

		$this->bbcodes[] = $name = strtolower($name);
		$this->ret .= '[' . $name . ':' . self::$uid . ']';

		$this->tag_closed = false;
	}

	public function writeAttribute($k, $v)
	{
		$q = (end($this->bbcodes) === 'quote') ? '&quot;' : '';

		$this->ret = substr($this->ret, 0, strrpos($this->ret, ':'))
		           . '='
		           . $q . $this->escape($v) . $q
		           . ':' . self::$uid
		           . ']';
	}

	protected function escape($str)
	{
		return str_replace(
			array('<',    '>',    '[',     ']',     '.',     ':'    ),
			array('&lt;', '&gt;', '&#91;', '&#93;', '&#46;', '&#58;'),
			$str
		);
	}

	public function writeElement($name, $content = '')
	{
		if ($name === 'st')
		{
		}
		elseif ($name === 'et')
		{
			$this->tag_closed = true;
		}
		else
		{
			throw new Exception("Didn't see that one coming");
		}
	}

	public function text($text)
	{
		$this->ret .= (end($this->bbcodes) === 'code') ? $this->escape($text) : htmlspecialchars($text);
	}

	public function endElement()
	{
		$bbcode_id = array_pop($this->bbcodes);

		$ws = $magic = '';
		if ($bbcode_id === '*' && !$this->tag_closed)
		{
			$magic = ':m';
			$ret   = rtrim($this->ret);

			if ($ret !== $this->ret)
			{
				$ws        = substr($this->ret, strlen($ret));
				$this->ret = $ret;
			}
		}

		$this->ret .= '[/' . $bbcode_id . $magic . ':' . self::$uid . ']' . $ws;
		$this->tag_closed = false;
	}

	public function endDocument()
	{
		while (!empty($this->bbcodes))
		{
			$this->endElement();
		}

		$this->ret = preg_replace_callback(
			'#\\[smiley:' . self::$uid . '\\]([^\\[]+)\\[/smiley:' . self::$uid . '\\]#',
			array('self', 'replaceSmiley'),
			$this->ret
		);
	}

	protected function replaceSmiley($m)
	{
		return self::$smilies[$m[1]];
	}

	public function outputMemory()
	{
		return $this->ret;
	}

	public function filterFontSize($v, $conf, &$msgs)
	{
		return max(min($v, $conf['max']), $conf['min']);
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