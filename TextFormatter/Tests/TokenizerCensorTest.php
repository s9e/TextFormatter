<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TokenizerCensorTest extends \PHPUnit_Framework_TestCase
{
	public function testExactCensoring()
	{
		$config   = $this->getConfig(array('apple' => 'pear'));

		$text     = 'You dirty apple';
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
                    'len'    => 5,
                    'params' => array('replacement' => 'pear')
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testExactCensoringWithNoReplacement()
	{
		$config   = $this->getConfig(array('apple' => null));

		$text     = 'You dirty apple';
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
                    'len'    => 5
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testExactCensorDoesNotApplyToPartialMatch()
	{
		$config   = $this->getConfig(array('apple' => 'pear'));

		$text     = 'You dirty applepie';
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testPartialCensorAppliesToPartialMatch()
	{
		$config   = $this->getConfig(array('apple*' => 'pear'));

		$text     = 'You dirty applepie';
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
                    'len'    => 8,
                    'params' => array('replacement' => 'pear')
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testCensoredUnicodeWordAppliesToUnicodeText()
	{
		$config   = $this->getConfig(array("pok\xC3\xA9*" => null));

		$text     = "You dirty Pok\xC3\xA9man";
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
					// length in bytes
                    'len'    => 8
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testCensoredUnicodeWordAppliesToASCIITextWithUnicodeReplacement()
	{
		$config   = $this->getConfig(array("pok\xC3\xA9man" => "Pok\xC3\xA9mon"));

		$text     = "You dirty Pok\xC3\xA9man";
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
					// length in bytes
                    'len'    => 8,
					'params' => array('replacement' => "Pok\xC3\xA9mon")
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	public function testCensoredASCIIWordAppliesToUnicodeText()
	{
		$config   = $this->getConfig(array("pok*" => null));

		$text     = "You dirty Pok\xC3\xA9man";
		$actual   = $this->parse($text, $config);
		$expected = array(
			'tags' => array(
				array(
					'pos'    => 10,
                    'name'   => 'CENSOR',
					// length in bytes
                    'len'    => 8
				)
			),
			'msgs' => array()
		);

		$this->assertKindaEquals($expected, $actual);
	}

	protected function getConfig($words)
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('censor');
		$cb->addBBCodeParam('censor', 'replacement', 'text', array('isRequired' => false));

		$cb->setCensorOption('bbcode', 'censor');
		$cb->setCensorOption('param', 'replacement');
		$cb->setCensorOption('limit', 10);

		foreach ($words as $word => $replacement)
		{
			$cb->addCensor($word, $replacement);
		}

		return $cb->getCensorConfig();
	}

	protected function assertKindaEquals($expected, $actual)
	{
		foreach ($expected as $type => $content)
		{
			$this->assertArrayHasKey($type, $actual);

			if (count($expected[$type]) !== count($actual[$type]))
			{
				$this->assertEquals($expected[$type], $actual[$type]);
			}

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

	protected function parse($text, $config)
	{
		$matches = array();
		foreach ($config['regexp'] as $k => $regexp)
		{
			preg_match_all($regexp, $text, $matches[$k], \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
		}
		return Parser::getCensorTags($text, $config, $matches);
	}
}