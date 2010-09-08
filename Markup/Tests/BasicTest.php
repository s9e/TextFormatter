<?php

namespace s9e\Toolkit\Markup\Tests;

use s9e\Toolkit\Markup\ConfigBuilder,
    s9e\Toolkit\Markup\Parser,
    s9e\Toolkit\Markup\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class BasicTest extends \PHPUnit_Framework_TestCase
{
	public function testPlainText()
	{
		$text     = 'This is some plain text.';
		$expected = '<pt>This is some plain text.</pt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testPlainTextResultIsReversible()
	{
		$text   = 'This is some plain text.';
		$xml    = $this->parser->parse($text);

		$actual = html_entity_decode(strip_tags($xml));

		$this->assertSame($text, $actual);
	}

	public function testRichText()
	{
		$text     = 'This is some [b]bold[/b] text.';
		$expected = '<rt>This is some <B><st>[b]</st>bold<et>[/b]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testRichTextResultIsReversible()
	{
		$text   = "This is some [b]bold[/b] text with special \"'& \xE2\x99\xA5<characters>\r\n"
		        . '...and line breaks too.';
		$xml    = $this->parser->parse($text);

		$actual = html_entity_decode(strip_tags($xml));

		$this->assertSame($text, $actual);
	}

	public function testNestingLimitIsRespected()
	{
		$text     = 'This is some [b][b]bold[/b] text.';
		$expected = '<rt>This is some <B><st>[b]</st>[b]bold<et>[/b]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testNestingLimitIsRespected
	*/
	public function testBBCodeSuffix()
	{
		$text     = 'This is some [b:123][b]bold[/b][/b:123] text.';
		$expected = '<rt>This is some <B><st>[b:123]</st>[b]bold[/b]<et>[/b:123]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testEmoticon()
	{
		$text     = 'test :) :)';
		$expected = '<rt>test <E>:)</E> <E>:)</E></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testBBCodesFromTokenizersAreUppercasedIfNeeded()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');

		$config = $cb->getParserConfig();

		$config['passes']['custom'] = array(
			'parser' => function()
			{
				return array(
					'tags' => array(
						array(
							'pos'  => 0,
							'len'  => 0,
							'type' => Parser::TAG_OPEN,
							'name' => 'b'
						),
						array(
							'pos'  => 3,
							'len'  => 0,
							'type' => Parser::TAG_CLOSE,
							'name' => 'B'
						)
					)
				);
			}
		);

		$parser = new Parser($config);

		$expected = '<rt><B>foo</B></rt>';
		$actual   = $parser->parse('foo');

		$this->assertSame($expected, $actual);
	}

	public function testUnknownBBCodesAreIgnored()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');
		$cb->addBBCode('i');

		/**
		* It is possible that an application would selectively disable BBCodes by altering the
		* config rather than regenerate a whole new one. We make sure stuff doesn't go haywire
		*/
		$config = $cb->getParserConfig();
		unset($config['passes']['BBCode']['aliases']['I']);
		unset($config['passes']['BBCode']['bbcodes']['I']);

		$parser = new Parser($config);

		$text     = '[i]foo[/i]';
		$expected = '<pt>[i]foo[/i]</pt>';
		$actual   = $parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testUnknownBBCodesFromCustomPassesAreIgnored()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');

		$config = $cb->getParserConfig();

		$config['passes']['custom'] = array(
			'parser' => function()
			{
				return array(
					'tags' => array(
						array(
							'pos'  => 0,
							'len'  => 0,
							'type' => Parser::TAG_OPEN,
							'name' => 'Z'
						),
						array(
							'pos'  => 3,
							'len'  => 0,
							'type' => Parser::TAG_CLOSE,
							'name' => 'Z'
						)
					)
				);
			}
		);

		$parser = new Parser($config);

		$expected = '<pt>foo</pt>';
		$actual   = $parser->parse('foo');

		$this->assertSame($expected, $actual);
	}

	public function testAutolink()
	{
		$text     = 'Go to http://www.example.com for more';
		$expected = '<rt>Go to <A href="http://www.example.com">http://www.example.com</A> for more</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testTokenizerLimitIsRespected()
	{
		$config = $this->config;
		$config['passes']['Censor']['limit'] = 1;
		$config['passes']['Censor']['limit_action'] = 'ignore';

		$parser = new Parser($config);

		$text     = 'You dirty banana banana grape';
		$expected = '<rt>You dirty <C with="pear">banana</C> banana grape</rt>';
		$actual   = $parser->parse($text);

		$this->assertSame($expected, $actual);

		$this->assertEquals(array(
			'debug' => array(
				array(
					'pos'    => 0,
					'msg'    => 'Censor limit exceeded. Only the first %s matches will be processed',
					'params' => array(1)
				)
			)
		), $parser->msgs);
	}

	/**
	* @expectedException Exception
	*/
	public function testTokenizerLimitExceededWithActionAbortThrowsAnException()
	{
		$config = $this->config;
		$config['passes']['Censor']['limit'] = 1;
		$config['passes']['Censor']['limit_action'] = 'abort';

		$parser = new Parser($config);
		$parser->parse('You dirty banana banana grape');
	}

	/**
	* @dataProvider getWhitespaceTrimming
	*/
	public function testWhitespaceTrimming($option, $text, $expected_html, $expected_xml)
	{
		$cb = new ConfigBuilder;

		$cb->addPass(
			'Foo',
			array(
				'parser' => function($text)
				{
					preg_match_all('#(?: FOOWS |FOO)#', $text, $matches, \PREG_OFFSET_CAPTURE);

					$tags = array();
					foreach ($matches[0] as $m)
					{
						$tags[] = array(
							'name' => 'foo',
							'type' => Parser::TAG_SELF,
							'pos'  => $m[1],
							'len'  => strlen($m[0])
						);
					}

					return array('tags' => $tags);
				}
			)
		);

		$cb->addBBCode('b');
		$cb->addBBCode('foo', array($option => true));

		$cb->setBBCodeTemplate('foo', '[foo]<xsl:apply-templates/>[/foo]');
		$cb->setBBCodeTemplate('b', '[b]<xsl:apply-templates/>[/b]');

		$actual_xml = $cb->getParser()->parse($text);
		$this->assertSame($expected_xml, $actual_xml);

		$actual_html = $cb->getRenderer()->render($expected_xml);
		$this->assertSame($expected_html, $actual_html);
	}

	public function getWhitespaceTrimming()
	{
		return array(
			array(
				'ltrim_content',
				'[b] [foo] 1 [/foo] 2 [foo] 3 [/foo] [/b]',
				'[b] [foo]1 [/foo] 2 [foo]3 [/foo] [/b]',
				'<rt><B><st>[b]</st> <FOO><st>[foo]</st><i> </i>1 <et>[/foo]</et></FOO> 2 <FOO><st>[foo]</st><i> </i>3 <et>[/foo]</et></FOO> <et>[/b]</et></B></rt>'
			),
			array(
				'rtrim_content',
				'[b] [foo] 1 [/foo] 2 [foo] 3 [/foo] [/b]',
				'[b] [foo] 1[/foo] 2 [foo] 3[/foo] [/b]',
				'<rt><B><st>[b]</st> <FOO><st>[foo]</st> 1<i> </i><et>[/foo]</et></FOO> 2 <FOO><st>[foo]</st> 3<i> </i><et>[/foo]</et></FOO> <et>[/b]</et></B></rt>'
			),
			array(
				'trim_before',
				'[b] [foo] 1 [/foo] 2 [foo] 3 [/foo] [/b]',
				'[b][foo] 1 [/foo] 2[foo] 3 [/foo] [/b]',
				'<rt><B><st>[b]</st><i> </i><FOO><st>[foo]</st> 1 <et>[/foo]</et></FOO> 2<i> </i><FOO><st>[foo]</st> 3 <et>[/foo]</et></FOO> <et>[/b]</et></B></rt>'
			),
			array(
				'trim_after',
				'[b] [foo] 1 [/foo] 2 [foo] 3 [/foo] [/b]',
				'[b] [foo] 1 [/foo]2 [foo] 3 [/foo][/b]',
				'<rt><B><st>[b]</st> <FOO><st>[foo]</st> 1 <et>[/foo]</et></FOO><i> </i>2 <FOO><st>[foo]</st> 3 <et>[/foo]</et></FOO><i> </i><et>[/b]</et></B></rt>'
			),
			array(
				'ltrim_content',
				'[b] FOOWS | FOOWS [/b]',
				'[b][foo]FOOWS [/foo]|[foo]FOOWS [/foo][/b]',
				'<rt><B><st>[b]</st><FOO><i> </i>FOOWS </FOO>|<FOO><i> </i>FOOWS </FOO><et>[/b]</et></B></rt>'
			),
			array(
				'rtrim_content',
				'[b] FOOWS | FOOWS [/b]',
				'[b][foo] FOOWS[/foo]|[foo] FOOWS[/foo][/b]',
				'<rt><B><st>[b]</st><FOO> FOOWS<i> </i></FOO>|<FOO> FOOWS<i> </i></FOO><et>[/b]</et></B></rt>'
			),
			array(
				'trim_before',
				'[b] FOO | FOO [/b]',
				'[b][foo]FOO[/foo] |[foo]FOO[/foo] [/b]',
				'<rt><B><st>[b]</st><i> </i><FOO>FOO</FOO> |<i> </i><FOO>FOO</FOO> <et>[/b]</et></B></rt>'
			),
			array(
				'trim_after',
				'[b] FOO | FOO [/b]',
				'[b] [foo]FOO[/foo]| [foo]FOO[/foo][/b]',
				'<rt><B><st>[b]</st> <FOO>FOO</FOO><i> </i>| <FOO>FOO</FOO><i> </i><et>[/b]</et></B></rt>'
			)
		);
	}

	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b', array('nesting_limit' => 1));

		$cb->addBBCode('a');
		$cb->addBBCodeParam('a', 'href', 'url', true);
		$cb->setAutolinkOption('bbcode', 'a');
		$cb->setAutolinkOption('param', 'href');

		$cb->addBBCode('x');
		$cb->addBBCodeParam('x', 'foo', 'text', false);

		$cb->addEmoticon(':)', '<img src="happy.png" alt=":)" />');

		$cb->addCensor('apple');
		$cb->addCensor('banana', 'pear');
		$cb->addCensor('grape*');

		$cb->addBBCode('c', array('internal_use' => true));
		$cb->addBBCodeParam('c', 'with', 'text', false);

		$cb->setCensorOption('bbcode', 'c');
		$cb->setCensorOption('param', 'with');

		$this->config = $cb->getParserConfig();
		$this->parser = new Parser($this->config);
	}
}