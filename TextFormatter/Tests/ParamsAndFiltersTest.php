<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class ParamsAndFiltersTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @dataProvider getParamStuff
	*/
	public function testParamStuff($text, $expected, $expected_msgs = array())
	{
		$actual = $this->parser->parse($text);
		$this->assertSame($expected, $actual);

		$actual_msgs = $this->parser->getLog();

		if (!isset($expected_msgs['debug']))
		{
			unset($actual_msgs['debug']);
		}

		$this->assertEquals($expected_msgs, $actual_msgs);
	}

	public function testBBCodeAliasesCanBeUsedWhenAddingParams()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('foo');
		$cb->addBBCodeAlias('foo', 'bar');
		$cb->addBBCodeParam('bar', 'baz', 'text');

		$text     = '[foo baz=TEXT]contents[/foo]';
		$expected = '<rt><FOO baz="TEXT"><st>[foo baz=TEXT]</st>contents<et>[/foo]</et></FOO></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testDefaultParamUsesBBCodeCanonicalName()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('foo');
		$cb->addBBCodeAlias('foo', 'bar');
		$cb->addBBCodeParam('foo', 'foo', 'text');

		$text     = '[bar=TEXT]contents[/bar]';
		$expected = '<rt><FOO foo="TEXT"><st>[bar=TEXT]</st>contents<et>[/bar]</et></FOO></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamPreFilter()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X');
		$cb->addBBCodeParam('X', 'y', 'text', array('pre_filter' => array('trim', 'strtolower')));

		$text     = '[X y=" ABC "][/X]';
		$expected = '<rt><X y="abc"><st>[X y=&quot; ABC &quot;]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamPostFilter()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X');
		$cb->addBBCodeParam('X', 'y', 'text', array('post_filter' => array('trim', 'strtolower')));

		$text     = '[X y=" ABC "][/X]';
		$expected = '<rt><X y="abc"><st>[X y=&quot; ABC &quot;]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testParamPreFilter
	* @depends testParamPostFilter
	*/
	public function testParamPreFilterAndPostFilterOrder()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X');
		$cb->addBBCodeParam('X', 'y', 'text', array(
			'pre_filter'  => array('strtolower'),
			'post_filter' => array('trim', 'strtoupper')
		));

		$text     = '[X y=" ABC "][/X]';
		$expected = '<rt><X y="ABC"><st>[X y=&quot; ABC &quot;]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPreFilter()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('pre_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'text');

		$text     = '[X y=FOO][/X]';
		$expected = '<rt><X y="Y"><st>[X y=FOO]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPreFilterWithNoParamInInput()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('pre_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'text');

		$text     = '[X][/X]';
		$expected = '<rt><X y="Y"><st>[X]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPreFilterWithInvalidParam()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('pre_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'int');

		$text     = '[X y=FOO][/X]';
		$expected = '<pt>[X y=FOO][/X]</pt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPostFilter()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('post_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'text');

		$text     = '[X y=FOO][/X]';
		$expected = '<rt><X y="Y"><st>[X y=FOO]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPostFilterWithNoParamInInput()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('post_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'text');

		$text     = '[X][/X]';
		$expected = '<rt><X y="Y"><st>[X]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParamsPostFilterAllowsInvalidParams()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('X', array('post_filter' => array(function() { return array('y' => 'Y'); })));
		$cb->addBBCodeParam('X', 'y', 'int');

		$text     = '[X y=FOO][/X]';
		$expected = '<rt><X y="Y"><st>[X y=FOO]</st><et>[/X]</et></X></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}


	public function getParamStuff()
	{
		return array(
			array(
				'[x unknown=123 /]',
				'<rt><X>[x unknown=123 /]</X></rt>'
			),
			array(
				'[x foo="[b]bar[/b]" /]',
				'<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>'
			),
			array(
				'[url]foo[/url]',
				'<pt>[url]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			array(
				'[url href=http://www.example.com]foo[/url]',
				'<rt><URL href="http://www.example.com"><st>[url href=http://www.example.com]</st>foo<et>[/url]</et></URL></rt>'
			),
			array(
				'[url href=ftp://www.example.com]foo[/url]',
				'<rt><URL href="ftp://www.example.com"><st>[url href=ftp://www.example.com]</st>foo<et>[/url]</et></URL></rt>'
			),
			array(
				'[url href=http://bevil.example.com]foo[/url]',
				'<rt><URL href="http://bevil.example.com"><st>[url href=http://bevil.example.com]</st>foo<et>[/url]</et></URL></rt>'
			),
			array(
				'[url href="javascript:alert()"]foo[/url]',
				'<pt>[url href=&quot;javascript:alert()&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'Invalid param %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			// optional param has invalid content - we keep the tag, discard the invalid param
			array(
				'[x number=123abc /]',
				'<rt><X>[x number=123abc /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'number',
							'msg'       => 'Invalid param %s',
							'params'    => array('number')
						)
					)
				)
			),
			array(
				'[x number=123 /]',
				'<rt><X number="123">[x number=123 /]</X></rt>'
			),
			array(
				'[x integer=123 /]',
				'<rt><X integer="123">[x integer=123 /]</X></rt>'
			),
			array(
				'[x int=123 /]',
				'<rt><X int="123">[x int=123 /]</X></rt>'
			),
			array(
				'[x int=-123 /]',
				'<rt><X int="-123">[x int=-123 /]</X></rt>'
			),
			array(
				'[x integer=123.1 /]',
				'<rt><X>[x integer=123.1 /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'integer',
							'msg'       => 'Invalid param %s',
							'params'    => array('integer')
						)
					)
				)
			),
			array(
				'[x uint=123 /]',
				'<rt><X uint="123">[x uint=123 /]</X></rt>'
			),
			array(
				'[x uint=-123 /]',
				'<rt><X>[x uint=-123 /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'uint',
							'msg'       => 'Invalid param %s',
							'params'    => array('uint')
						)
					)
				)
			),
			array(
				'[x id=123 /]',
				'<rt><X id="123">[x id=123 /]</X></rt>'
			),
			array(
				'[x id=123abc /]',
				'<rt><X id="123abc">[x id=123abc /]</X></rt>'
			),
			array(
				'[x id=-123_abc /]',
				'<rt><X id="-123_abc">[x id=-123_abc /]</X></rt>'
			),
			array(
				'[x identifier="-123_abc" /]',
				'<rt><X identifier="-123_abc">[x identifier=&quot;-123_abc&quot; /]</X></rt>'
			),
			array(
				'[x identifier="123 abc" /]',
				'<rt><X>[x identifier=&quot;123 abc&quot; /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'identifier',
							'msg'       => 'Invalid param %s',
							'params'    => array('identifier')
						)
					)
				)
			),
			array(
				'[x color=#123 /]',
				'<rt><X color="#123">[x color=#123 /]</X></rt>'
			),
			array(
				'[x color=blue /]',
				'<rt><X color="blue">[x color=blue /]</X></rt>'
			),
			array(
				'[x color=123 /]',
				'<rt><X>[x color=123 /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'color',
							'msg'       => 'Invalid param %s',
							'params'    => array('color')
						)
					)
				)
			),
			array(
				'[x custom="foo" /]',
				'<rt><X custom="foo">[x custom=&quot;foo&quot; /]</X></rt>'
			),
			array(
				'[x simpletext="foo bar baz" /]',
				'<rt><X simpletext="foo bar baz">[x simpletext=&quot;foo bar baz&quot; /]</X></rt>'
			),
			array(
				'[x simpletext="foo \'bar\' baz" /]',
				'<rt><X>[x simpletext=&quot;foo \'bar\' baz&quot; /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'simpletext',
							'msg'       => 'Invalid param %s',
							'params'    => array('simpletext')
						)
					)
				)
			),
			array(
				'[url href="http://evil.example.com"]foo[/url]',
				'<pt>[url href=&quot;http://evil.example.com&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'URL host %s is not allowed',
							'params'   => array('evil.example.com')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'Invalid param %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			array(
				'[url href="http://reallyevil.example.com"]foo[/url]',
				'<pt>[url href=&quot;http://reallyevil.example.com&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'URL host %s is not allowed',
							'params'    => array('reallyevil.example.com')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'Invalid param %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			array(
				'[url href="http://example.xxx"]foo[/url]',
				'<pt>[url href=&quot;http://example.xxx&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'URL host %s is not allowed',
							'params'    => array('example.xxx')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'Invalid param %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			array(
				'[url href="evil://example.com"]foo[/url]',
				'<pt>[url href=&quot;evil://example.com&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'URL scheme %s is not allowed',
							'params'    => array('evil')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'paramName' => 'href',
							'msg'       => 'Invalid param %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'URL',
							'msg'       => 'Missing param %s',
							'params'    => array('href')
						)
					)
				)
			),
			array(
				'[x undefined=123 /]',
				'<rt><X>[x undefined=123 /]</X></rt>',
				array(
					'debug' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'undefined',
							'msg'       => 'Unknown filter %s',
							'params'    => array('undefined')
						)
					),
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'undefined',
							'msg'       => 'Invalid param %s',
							'params'    => array('undefined')
						)
					)
				)
			),
			array(
				'[x float=123 /]',
				'<rt><X float="123">[x float=123 /]</X></rt>'
			),
			array(
				'[x float=123.0 /]',
				'<rt><X float="123">[x float=123.0 /]</X></rt>'
			),
			array(
				'[x float=123.45 /]',
				'<rt><X float="123.45">[x float=123.45 /]</X></rt>'
			),
			array(
				'[x float=-123.45 /]',
				'<rt><X float="-123.45">[x float=-123.45 /]</X></rt>'
			),
			array(
				'[x float=123 /]',
				'<rt><X float="123">[x float=123 /]</X></rt>'
			),
			array(
				'[x float=123z /]',
				'<rt><X>[x float=123z /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'float',
							'msg'       => 'Invalid param %s',
							'params'    => array('float')
						)
					)
				)
			),
			array(
				'[align=left]content[/align]',
				'<rt><ALIGN align="left"><st>[align=left]</st>content<et>[/align]</et></ALIGN></rt>'
			),
			array(
				'[align=INVALID]content[/align]',
				'<pt>[align=INVALID]content[/align]</pt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'ALIGN',
							'paramName' => 'align',
							'msg'       => 'Invalid param %s',
							'params'    => array('align')
						),
						array(
							'pos'       => 0,
							'bbcodeId'  => 'ALIGN',
							'msg'       => 'Missing param %s',
							'params'    => array('align')
						)
					)
				)
			),
			array(
				' [x uint=-123 /]',
				'<rt> <X>[x uint=-123 /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 1,
							'bbcodeId'  => 'X',
							'paramName' => 'uint',
							'msg'       => 'Invalid param %s',
							'params'    => array('uint')
						)
					)
				)
			),
			array(
				'[x range=-123 /]',
				'<rt><X range="7">[x range=-123 /]</X></rt>',
				array(
					'info' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'range',
							'msg'       => 'Minimum range value adjusted to %s',
							'params'    => array(7)
						)
					)
				)
			),
			array(
				'[x range=123 /]',
				'<rt><X range="77">[x range=123 /]</X></rt>',
				array(
					'info' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'X',
							'paramName' => 'range',
							'msg'       => 'Maximum range value adjusted to %s',
							'params'    => array(77)
						)
					)
				)
			),
			array(
				'[size=1]too small[/size]',
				'<rt><SIZE size="7"><st>[size=1]</st>too small<et>[/size]</et></SIZE></rt>',
				array(
					'warning' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'SIZE',
							'paramName' => 'size',
							'msg'       => 'Font size must be at least %d',
							'params'    => array(7)
						)
					)
				)
			),
			array(
				'[size=99]too big[/size]',
				'<rt><SIZE size="20"><st>[size=99]</st>too big<et>[/size]</et></SIZE></rt>',
				array(
					'warning' => array(
						array(
							'pos'       => 0,
							'bbcodeId'  => 'SIZE',
							'paramName' => 'size',
							'msg'       => 'Font size is limited to %d',
							'params'    => array(20)
						)
					)
				)
			)
		);
	}

	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b');

		$cb->addBBCode('url');
		$cb->addBBCodeParam('url', 'href', 'url');
		$cb->addBBCodeParam('url', 'title', 'text', array('is_required' => false));

		$cb->addBBCode('x');
		$cb->addBBCodeParam('x', 'foo', 'text', array('is_required' => false));
		$cb->addBBCodeParam('x', 'range', 'range', array('is_required' => false, 'min' => 7, 'max' => 77));

		$types = array(
			'custom',
			'float',
			'integer', 'int',
			'number', 'uint',
			'id', 'identifier',
			'color',
			'simpletext',
			'undefined'
		);

		foreach ($types as $type)
		{
			$cb->addBBCodeParam('x', $type, $type, array('is_required' => false));
		}

		$cb->setFilter('custom', function($v) { return $v; });

		// [size] BBCode with custom font-size filter
		$cb->addBBCode('size', array('default_param' => 'size'));
		$cb->addBBCodeParam('size', 'size', 'font-size');

		$that = $this;
		$callback = function($v, $conf) use ($that)
		{
			if ($v < $conf['min'])
			{
				$that->parser->log('warning', array(
					'msg'    => 'Font size must be at least %d',
					'params' => array($conf['min'])
				));
				return $conf['min'];
			}
			elseif ($v > $conf['max'])
			{
				$that->parser->log('warning', array(
					'msg'    => 'Font size is limited to %d',
					'params' => array($conf['max'])
				));
				return $conf['max'];
			}
			return $v;
		};
		$cb->setFilter('font-size', $callback, array(
			'min' => 7,
			'max' => 20
		));

		$cb->disallowHost('EVIL.example.com');
		$cb->disallowHost('*.xxx');
		$cb->disallowHost('reallyevil.*');
		$cb->allowScheme('ftp');

		// Regexp stuff
		$cb->addBBCode('align');
		$cb->addBBCodeParam('align', 'align', 'regexp', array('regexp' => '/^(left|right)$/i'));

		$this->parser = $cb->getParser();
	}
}