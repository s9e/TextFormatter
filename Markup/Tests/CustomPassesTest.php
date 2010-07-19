<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class CustomPassesTest extends \PHPUnit_Framework_TestCase
{
	public function testBugTracker()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCodeFromExample(
			'[url={URL}]{TEXT}[/url]',
			'<a href="{URL}">{TEXT}</a>'
		);

		$cb->addPass('bugs', array(
			'parser' => array(get_class($this), 'getBugTags')
		));

		$text     = 'Check out bug #123 for more info';
		$expected = 'Check out bug <a href="http://bugs.example.com/123">#123</a> for more info';
		$actual   = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	static public function getBugTags($text, array $config)
	{
		$tags = $msgs = array();

		preg_match_all('/#([0-9]+)/', $text, $matches, PREG_OFFSET_CAPTURE);

		foreach ($matches[1] as $m)
		{
			list($bugId, $pos) = $m;

			// remove/add 1 to account for the # and make it part of the content
			--$pos;
			$len = strlen($bugId) + 1;

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
}