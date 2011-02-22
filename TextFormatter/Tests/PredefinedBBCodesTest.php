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
			array(
				'URL',
				'[URL url=http://www.example.org title="The best site ever"]GO THERE[/URL]',
				'<a href="http://www.example.org" title="The best site ever">GO THERE</a>'
			),
			array(
				'IMG',
				'[IMG]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png"/>'
			),
			array(
				'IMG',
				// no closing tag needed
				'[IMG=http://www.example.org/img.png]',
				'<img src="http://www.example.org/img.png"/>'
			),
			array(
				'IMG',
				// extraneous content is ignored
				'[IMG=http://www.example.org/img.png]TEXT[/IMG]',
				'<img src="http://www.example.org/img.png"/>'
			),
			array(
				'IMG',
				'[IMG alt="alt text"]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png" alt="alt text"/>'
			),
			array(
				'IMG',
				'[IMG title="Title"]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png" title="Title"/>'
			),
			array(
				'LIST',
				'[LIST][*]one[*]two[/LIST]',
				'<ol style="list-style-type:disc"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=1][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=01][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal-leading-zero"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=i][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=I][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=a][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-alpha"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=A][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-alpha"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=INVALID][*]one[*]two[/LIST]',
				'<ol style="list-style-type:disc"><li>one</li><li>two</li></ol>'
			),
			array(
				'LIST',
				'[LIST=square][*]one[*]two[/LIST]',
				'<ol style="list-style-type:square"><li>one</li><li>two</li></ol>'
			),
			array(
				'GOOGLEVIDEO',
				'[GOOGLEVIDEO]-4381488634998231167[/GOOGLEVIDEO]',
				'<object type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=-4381488634998231167" width="400" height="326"><param name="movie" value="http://video.google.com/googleplayer.swf?docId=-4381488634998231167"/><param name="allowScriptAcess" value="sameDomain"/><param name="quality" value="best"/><param name="scale" value="noScale"/><param name="salign" value="TL"/><param name="FlashVars" value="playerMode=embedded"/></object>'
			),
			array(
				'YOUTUBE',
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/-cEzsCAzTak" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/-cEzsCAzTak"/><param name="wmode" value="transparent"/></object>'
			),
		);
	}
}