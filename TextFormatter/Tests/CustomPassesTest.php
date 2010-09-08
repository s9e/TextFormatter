<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class CustomPassesTest extends \PHPUnit_Framework_TestCase
{
	//==========================================================================
	// Some kind of bugtracker autolinking bug numbers
	//==========================================================================

	public function testBugTracker()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCodeFromExample(
			'[url={URL}]{TEXT}[/url]',
			'<a href="{URL}">{TEXT}</a>'
		);

		$cb->addPass('BugTracker', array(
			'parser' => array(get_class($this), 'getBugTrackerTags'),
			'regexp' => '/#[0-9]+/'
		));

		$text     = 'Check out bug #123 for more info';
		$expected = 'Check out bug <a href="http://bugs.example.com/123">#123</a> for more info';
		$actual   = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	static public function getBugTrackerTags($text, array $config, array $matches)
	{
		$tags = $msgs = array();

		foreach ($matches as $m)
		{
			$bugId = substr($m[0][0], 1);
			$pos   = $m[0][1];
			$len   = strlen($m[0][0]);

			$tags[] = array(
				'name'   => 'URL',
				'type'   => Parser::TAG_OPEN,
				'pos'    => $pos,
				'len'    => 0,
				'params' => array('url' => 'http://bugs.example.com/' . $bugId)
			);

			$tags[] = array(
				'name'   => 'URL',
				'type'   => Parser::TAG_CLOSE,
				'pos'    => $pos + $len,
				'len'    => 0
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	//==========================================================================
	// The most limited implementation of Markdown ever
	//==========================================================================

	public function testMarkdown()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCodeFromExample('[em]{TEXT}[/em]', '<em>{TEXT}</em>');
		$cb->addBBCodeFromExample('[strong]{TEXT}[/strong]', '<strong>{TEXT}</strong>');
		$cb->addBBCodeFromExample('[url={URL}]{TEXT}[/url]', '<a href="{URL}">{TEXT}</a>');

		// No regexp here, the tokenizer will have do it itself
		$cb->addPass('Markdown', array(
			'parser' => array(get_class($this), 'getMarkdownTags')
		));

		$text     =
			'Some *emphasized* __strong__ text and an [example link](http://example.com/)';

		$expected =
			'Some <em>emphasized</em> <strong>strong</strong> text and an <a href="http://example.com/">example link</a>';

		$actual   = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	static public function getMarkdownTags($text, array $config)
	{
		$tags = $msgs = array();

		// match **strong** and __strong__
		if (preg_match_all('#(\\*\\*|__)[^\\*_]+\\1#S', $text, $matches, \PREG_OFFSET_CAPTURE))
		{
			foreach ($matches[0] as $m)
			{
				$pos = $m[1];

				$tags[] = array(
					'name'   => 'STRONG',
					'type'   => Parser::TAG_OPEN,
					'pos'    => $pos,
					// consume the opening ** or __
					'len'    => 2
				);

				$tags[] = array(
					'name'   => 'STRONG',
					'type'   => Parser::TAG_CLOSE,
					'pos'    => $pos + strlen($m[0]) - 2,
					// consume the closing ** or __
					'len'    => 2
				);
			}
		}

		// match *em* and _em_
		if (preg_match_all('#(\\*|_)[^\\*_]+\\1#S', $text, $matches, \PREG_OFFSET_CAPTURE))
		{
			foreach ($matches[0] as $m)
			{
				$pos = $m[1];

				$tags[] = array(
					'name'   => 'EM',
					'type'   => Parser::TAG_OPEN,
					'pos'    => $pos,
					'len'    => 1
				);

				$tags[] = array(
					'name'   => 'EM',
					'type'   => Parser::TAG_CLOSE,
					'pos'    => $pos + strlen($m[0]) - 1,
					'len'    => 1
				);
			}
		}

		// match [example link](http://example.com/)
		// We don't have to validate/filter the URL, it will be done by the parser
		if (preg_match_all('#\\[([^\\]]+)\\]\\(([a-z]+://[^\\)]+)\\)#i', $text, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER))
		{
			foreach ($matches as $m)
			{
				$tags[] = array(
					'name'   => 'URL',
					'type'   => Parser::TAG_OPEN,
					'pos'    => $m[0][1],
					// consume the first [
					'len'    => 1,
					'params' => array('url' => $m[2][0])
				);

				$tags[] = array(
					'name'   => 'URL',
					'type'   => Parser::TAG_CLOSE,
					// position the closing tag at the first ]
					'pos'    => $m[2][1] - 2,
					// consume everything from the ] to the )
					'len'    => strlen($m[2][0]) + 3
				);
			}
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	//==========================================================================
	// Insert [BR/] tags at each newline
	//==========================================================================

	public function testNewlines()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('br', array('internal_use' => true));
		$cb->setBBCodeTemplate('br', '<br/>');

		$cb->addPass('Newlines', array(
			'parser' => function($text)
			{
				preg_match_all('#\\r?\\n#', $text, $matches, \PREG_OFFSET_CAPTURE);

				$tags = array();
				foreach ($matches[0] as $m)
				{
					$tags[] = array(
						'name'   => 'BR',
						'type'   => Parser::TAG_SELF,
						'pos'    => $m[1],
						'len'    => strlen($m[0])
					);
				}

				return array(
					'tags' => $tags,
					'msgs' => array()
				);
			}
		));

		$text     = "Line 1.\nLine 2.\r\nLine 3.";
		$expected = "Line 1.<br/>Line 2.<br/>Line 3.";
		$actual   = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}
}