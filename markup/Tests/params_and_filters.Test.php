<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testParamsAndFilters extends \PHPUnit_Framework_TestCase
{
	/**
	* @dataProvider getParamStuff
	*/
	public function testParamStuff($text, $expected, $msgs = array())
	{
		$actual = $this->parser->parse($text);
		$this->assertSame($expected, $actual);

		foreach ($msgs as $type => $_msgs)
		{
			$this->assertArrayHasKey($type, $this->parser->msgs);
			$this->assertEquals($_msgs, $this->parser->msgs[$type]);
		}
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
							'pos'    => 0,
							'msg'    => 'Missing param %s',
							'params' => array('href')
						)
					)
				)
			),
			array(
				'[url href=http://www.example.com]foo[/url]',
				'<rt><URL href="http://www.example.com"><st>[url href=http://www.example.com]</st>foo<et>[/url]</et></URL></rt>'
			),
			array(
				'[url href="javascript:alert()"]foo[/url]',
				'<pt>[url href=&quot;javascript:alert()&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('href')
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
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('number')
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
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('integer')
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
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('uint')
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
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('color')
						)
					)
				)
			),
			array(
				'[x custom="foo" /]',
				'<rt><X custom="foo">[x custom=&quot;foo&quot; /]</X></rt>'
			),
			array(
				'[url href="http://evil.example.com"]foo[/url]',
				'<pt>[url href=&quot;http://evil.example.com&quot;]foo[/url]</pt>',
				array(
					'error' => array(
						array(
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('href')
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
							'pos'    => 0,
							'msg'    => 'Invalid param %s',
							'params' => array('href')
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
							'msg'    => 'Unknown filter %s',
							'params' => array('undefined')
						)
					)
				)
			),
		);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->addBBCode('b');

		$cb->addBBCode('url');
		$cb->addBBCodeParam('url', 'href', 'url', true);
		$cb->addBBCodeParam('url', 'title', 'text', false);

		$cb->addBBCode('x');
		$cb->addBBCodeParam('x', 'foo', 'text', false);

		foreach (array('custom', 'number', 'integer', 'int', 'uint', 'color', 'undefined') as $type)
		{
			$cb->addBBCodeParam('x', $type, $type, false);
		}

		$cb->setFilter('custom', function($v) { return $v; });

		$cb->disallowHost('EVIL.example.com');

		$this->parser = new parser($cb->getParserConfig());
	}
}