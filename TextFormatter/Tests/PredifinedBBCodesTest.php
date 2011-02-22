<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';

class PredefinedBBCodesTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException InvalidArgumentException UNKNOWN
	*/
	public function testAddPredefinedBBCodeThrowsAnExceptionOnUnknownBBCodes()
	{
		$cb = new ConfigBuilder;
		$cb->addPredefinedBBCode('UNKNOWN');
	}

	/**
	* @dataProvider provider
	*/
	public function testPredefinedBBCodes($bbcodeId, $text, $expected)
	{
		$cb = new ConfigBuilder;
		$cb->addPredefinedBBCode($bbcodeId);

		$actual = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	public function provider()
	{
		return array(
			array(
				'B',
				'[B]bold [B]bolder[/B][/B]',
				'<strong>bold <strong>bolder</strong></strong>'
			),
			array(
				'I',
				'[I]italic [I]italicer[/I][/I]',
				'<em>italic <em>italicer</em></em>'
			),
			array(
				'U',
				'[U]underlined [U]well, still underlined[/U][/U]',
				'<span style="text-decoration: underline">underlined <span style="text-decoration: underline">well, still underlined</span></span>'
			),
			array(
				'S',
				'[S]strike [S]still striked[/S][/S]',
				'<span style="text-decoration: line-through">strike <span style="text-decoration: line-through">still striked</span></span>'
			),
			array(
				'URL',
				'[URL]http://www.example.org[/URL]',
				'<a href="http://www.example.org">http://www.example.org</a>'
			),
			array(
				'URL',
				'[URL=http://www.example.org]example.org[/URL]',
				'<a href="http://www.example.org">example.org</a>'
			),
		);
	}
}