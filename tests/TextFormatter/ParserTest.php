<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer,
    s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Parser
*/
class ParserTest extends Test
{
	public function setUp()
	{
		$this->cb = new ConfigBuilder;
	}

	protected function assertParsing($text, $expectedXml, $expectedLog = array('error' => null))
	{
		$parser    = $this->cb->getParser();
		$actualXml = $parser->parse($text);

		$this->assertSame($expectedXml, $actualXml);
		$this->assertArrayMatches($expectedLog, $parser->getLog());
	}

	//==========================================================================
	// Rules
	//==========================================================================

	public function testFulfilledRequireParentRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][b]stuff[/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testFulfilledRequireParentRuleAllowsTagDespitePrefix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a:123][b]stuff[/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testUnfulfilledRequireParentRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'pos'     => 0,
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

	public function testUnfulfilledRequireParentRuleBlocksTagDespiteAscendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][c][b]stuff[/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st>[b]stuff[/b]<et>[/c]</et></C><et>[/a]</et></A></rt>',
			array(
				'error' => array(
					array(
						'pos'     => 6,
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

	public function testCloseParentRuleIsApplied()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p]one[p]two',
			'<rt><P><st>[p]</st>one</P><P><st>[p]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithIdenticalSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:123]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:123]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithDifferentSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:456]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:456]</st>two</P></rt>'
		);
	}

	public function testDenyRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'allow'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'deny', 'b');

		$this->assertParsing(
			'[a]..[b][/b]..[/a]',
			'<rt><A><st>[a]</st>..[b][/b]..<et>[/a]</et></A></rt>'
		);
	}

	public function testAllowRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'deny'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'allow', 'b');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByParent()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @depends testRequireAscendantRuleIsFulfilledByParent
	*/
	public function testRequireAscendantRuleIsFulfilledByParentWithSuffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][b][/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByAscendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][c][b][/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @depends testRequireAscendantRuleIsFulfilledByAscendant
	*/
	public function testRequireAscendantRuleIsFulfilledByAscendantWithSuffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][c][b][/b][/c][/a:123]',
			'<rt><A><st>[a:123]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a:123]</et></A></rt>'
		);
	}

	public function testUnfulfilledRequireAscendantRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'pos'     => 0,
						'msg'     => 'Tag %1$s requires %2$s as ascendant',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

	//==========================================================================
	// Filters
	//==========================================================================

	/**
	* @dataProvider getParamStuff
	*/
	public function testParamStuff()
	{
		$this->cb->BBCodes->addBBCode('b');

		$this->cb->BBCodes->addBBCode('url');
		$this->cb->addTagAttribute('url', 'href', 'url');
		$this->cb->addTagAttribute('url', 'title', 'text', array('isRequired' => false));

		$this->cb->BBCodes->addBBCode('x');
		$this->cb->addTagAttribute('x', 'foo', 'text', array('isRequired' => false));
		$this->cb->addTagAttribute('x', 'range', 'range', array('isRequired' => false, 'min' => 7, 'max' => 77));
		$this->cb->addTagAttribute('x', 'replace', 'regexp', array(
			'isRequired' => false,
			'regexp'  => '/^(FOO)(BAR)$/',
			'replace' => '$2$1'
		));

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
			$this->cb->addTagAttribute('x', $type, $type, array('isRequired' => false));
		}

		$this->cb->setFilter('custom', function($v) { return $v; });

		// [size] BBCode with custom font-size filter
		$this->cb->BBCodes->addBBCode('size', array('defaultParam' => 'size'));
		$this->cb->addTagAttribute('size', 'size', 'font-size');

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
		$this->cb->setFilter('font-size', $callback, array(
			'min' => 7,
			'max' => 20
		));

		$this->cb->disallowHost('EVIL.example.com');
		$this->cb->disallowHost('*.xxx');
		$this->cb->disallowHost('reallyevil.*');
		$this->cb->allowScheme('ftp');

		// Regexp stuff
		$this->cb->BBCodes->addBBCode('align');
		$this->cb->addTagAttribute('align', 'align', 'regexp', array('regexp' => '/^(left|right)$/i'));

		call_user_func_array(array($this, 'assertParsing'), func_get_args());
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
							'tagName'  => 'URL',
							'msg'       => 'Missing attribute %s',
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
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'msg'       => 'Missing attribute %s',
							'params'    => array('href')
						)
					)
				)
			),
			// optional attribute has invalid content - we keep the tag, discard the invalid param
			array(
				'[x number=123abc /]',
				'<rt><X>[x number=123abc /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'X',
							'attrName' => 'number',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'integer',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'uint',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'identifier',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'color',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'simpletext',
							'msg'       => 'Invalid attribute %s',
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
							'pos'      => 0,
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'      => 'URL host %s is not allowed',
							'params'   => array('evil.example.com')
						),
						array(
							'pos'      => 0,
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'      => 'Invalid attribute %s',
							'params'   => array('href')
						),
						array(
							'pos'      => 0,
							'tagName'  => 'URL',
							'msg'      => 'Missing attribute %s',
							'params'   => array('href')
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
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'URL host %s is not allowed',
							'params'    => array('reallyevil.example.com')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'msg'       => 'Missing attribute %s',
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
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'URL host %s is not allowed',
							'params'    => array('example.xxx')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'msg'       => 'Missing attribute %s',
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
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'URL scheme %s is not allowed',
							'params'    => array('evil')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'attrName' => 'href',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('href')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'URL',
							'msg'       => 'Missing attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'undefined',
							'msg'       => "Unknown filter '%s'",
							'params'    => array('undefined')
						)
					),
					'error' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'X',
							'attrName' => 'undefined',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'float',
							'msg'       => 'Invalid attribute %s',
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
							'tagName'  => 'ALIGN',
							'attrName' => 'align',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('align')
						),
						array(
							'pos'       => 0,
							'tagName'  => 'ALIGN',
							'msg'       => 'Missing attribute %s',
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
							'tagName'  => 'X',
							'attrName' => 'uint',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('uint')
						)
					)
				)
			),
			array(
				'[x range=-123 /]',
				'<rt><X range="7">[x range=-123 /]</X></rt>',
				array(
					'warning' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'X',
							'attrName' => 'range',
							'msg'       => 'Minimum range value adjusted to %s',
							'params'    => array(7)
						)
					)
				)
			),
			array(
				'[x range=7 /]',
				'<rt><X range="7">[x range=7 /]</X></rt>'
			),
			array(
				'[x range=123 /]',
				'<rt><X range="77">[x range=123 /]</X></rt>',
				array(
					'warning' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'X',
							'attrName' => 'range',
							'msg'       => 'Maximum range value adjusted to %s',
							'params'    => array(77)
						)
					)
				)
			),
			array(
				'[x range=TWENTY /]',
				'<rt><X>[x range=TWENTY /]</X></rt>',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'X',
							'attrName' => 'range',
							'msg'       => 'Invalid attribute %s',
							'params'    => array('range')
						)
					)
				)
			),
			array(
				'[x replace=FOOBAR][/x]',
				'<rt><X replace="BARFOO"><st>[x replace=FOOBAR]</st><et>[/x]</et></X></rt>'
			),
/**
			array(
				'[size=1]too small[/size]',
				'<rt><SIZE size="7"><st>[size=1]</st>too small<et>[/size]</et></SIZE></rt>',
				array(
					'warning' => array(
						array(
							'pos'       => 0,
							'tagName'  => 'SIZE',
							'attrName' => 'size',
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
							'tagName'  => 'SIZE',
							'attrName' => 'size',
							'msg'       => 'Font size is limited to %d',
							'params'    => array(20)
						)
					)
				)
			)
/**/
		);
	}
}