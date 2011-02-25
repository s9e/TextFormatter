<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PredefinedBBCodes,
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

	public function testGetCODEstx()
	{
		$cb = new ConfigBuilder;
		$cb->addPredefinedBBCode('CODE');

		$xml = $cb->getParser()->parse('[code=php]//[/code][code=php]//[/code][code=html][/code]');

		$this->assertEquals(
			array('php', 'html'),
			PredefinedBBCodes::getCODEstx($xml)
		);
	}

	/**
	* @dataProvider provider
	*/
	public function testPredefinedBBCodes($text, $expected, array $expectedLog = array())
	{
		preg_match('#(?<=\\[)[a-z_0-9]+#i', $text, $m);
		$bbcodeId = $m[0];

		$cb = new ConfigBuilder;
		$cb->addPredefinedBBCode($bbcodeId);

		$parser = $cb->getParser();

		$actual = $cb->getRenderer()->render($parser->parse($text));
		$this->assertSame($expected, $actual);

		$actualLog = $parser->getLog();

		foreach (array_keys($expectedLog) as $type)
		{
			$this->assertArrayHasKey($type, $actualLog);
			$this->assertEquals($expectedLog[$type], $actualLog[$type]);
		}
	}

	public function provider()
	{
		return array(
			array(
				'[B]bold [B]bolder[/B][/B]',
				'<strong>bold <strong>bolder</strong></strong>'
			),
			array(
				'[I]italic [I]italicer[/I][/I]',
				'<em>italic <em>italicer</em></em>'
			),
			array(
				'[U]underlined [U]well, still underlined[/U][/U]',
				'<span style="text-decoration: underline">underlined <span style="text-decoration: underline">well, still underlined</span></span>'
			),
			array(
				'[S]strike [S]still striked[/S][/S]',
				'<span style="text-decoration: line-through">strike <span style="text-decoration: line-through">still striked</span></span>'
			),
			array(
				'[URL]http://www.example.org[/URL]',
				'<a href="http://www.example.org">http://www.example.org</a>'
			),
			array(
				'[URL=http://www.example.org]example.org[/URL]',
				'<a href="http://www.example.org">example.org</a>'
			),
			array(
				'[URL url=http://www.example.org title="The best site ever"]GO THERE[/URL]',
				'<a href="http://www.example.org" title="The best site ever">GO THERE</a>'
			),
			array(
				'[IMG]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png">'
			),
			array(
				'[IMG=http://www.example.org/img.png /]',
				'<img src="http://www.example.org/img.png">'
			),
			array(
				// extraneous content is ignored
				'[IMG=http://www.example.org/img.png]TEXT[/IMG]',
				'<img src="http://www.example.org/img.png">'
			),
			array(
				'[IMG alt="alt text"]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png" alt="alt text">'
			),
			array(
				'[IMG title="Title"]http://www.example.org/img.png[/IMG]',
				'<img src="http://www.example.org/img.png" title="Title">'
			),
			array(
				'[LIST][*]one[*]two[/LIST]',
				'<ol style="list-style-type:disc"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=1][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=01][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal-leading-zero"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=i][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=I][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=a][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-alpha"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=A][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-alpha"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=INVALID][*]one[*]two[/LIST]',
				'<ol style="list-style-type:disc"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=square][*]one[*]two[/LIST]',
				'<ol style="list-style-type:square"><li>one</li><li>two</li></ol>'
			),
			array(
				'[GOOGLEVIDEO]-4381488634998231167[/GOOGLEVIDEO]',
				'<object type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=-4381488634998231167" width="400" height="326"><param name="movie" value="http://video.google.com/googleplayer.swf?docId=-4381488634998231167"><param name="allowScriptAcess" value="sameDomain"><param name="quality" value="best"><param name="scale" value="noScale"><param name="salign" value="TL"><param name="FlashVars" value="playerMode=embedded"></object>'
			),
			array(
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/-cEzsCAzTak" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/-cEzsCAzTak"><param name="wmode" value="transparent"></object>'
			),
			array(
				'[YOUTUBE]http://nl.youtube.com/watch?v=-cEzsCAzTak[/YOUTUBE]',
				'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/-cEzsCAzTak" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/-cEzsCAzTak"><param name="wmode" value="transparent"></object>'
			),
			array(
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/-cEzsCAzTak" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/-cEzsCAzTak"><param name="wmode" value="transparent"></object>'
			),
			array(
				'[ALIGN=left]left[/ALIGN]',
				'<div style="text-align:left">left</div>'
			),
			array(
				'[ALIGN=left;color:red]LOL HAX[/ALIGN]',
				'[ALIGN=left;color:red]LOL HAX[/ALIGN]'
			),
			array(
				'[LEFT]left-aligned text[/LEFT]',
				'<div style="text-align:left">left-aligned text</div>'
			),
			array(
				'[RIGHT]right-aligned text[/RIGHT]',
				'<div style="text-align:right">right-aligned text</div>'
			),
			array(
				'[CENTER]centered text[/CENTER]',
				'<div style="text-align:center">centered text</div>'
			),
			array(
				'[JUSTIFY]justified text[/JUSTIFY]',
				'<div style="text-align:justify">justified text</div>'
			),
			array(
				'[BACKGROUND=red]text[/BACKGROUND]',
				'<span style="background-color:red">text</span>'
			),
			array(
				'[FONT="Times New Roman"]text[/FONT]',
				'<span style="font-family:Times New Roman">text</span>'
			),
			array(
				'[blink]{TEXT}[/blink]',
				'<span style="text-decoration:blink">{TEXT}</span>'
			),
			array(
				'[sub]{TEXT}[/sub]',
				'<span style="vertical-align:sub">{TEXT}</span>'
			),
			array(
				'[super]{TEXT}[/super]',
				'<span style="vertical-align:super">{TEXT}</span>'
			),
			array(
				'[TABLE]
					==MISPLACED TEXT IS IGNORED==
					[TR]
						[TH]col1[/TH]
						[TH]col2[/TH]
					[/TR]
					[TR]
						==MISPLACED TEXT IS IGNORED==
						[TD]cell1[/TD]
						[TD]cell2[/TD]
					[/TR]
					[TR]
						[TD rowspan=2]double height[/TD]
						[TD]x[/TD]
					[/TR]
					[TR]
						[TD]x[/TD]
					[/TR]
					[TR]
						[TD]cell1[/TD]
						[TD]cell2[/TD]
					[/TR]
					[TR]
						[TR]
						[TD colspan=2]double width[/TD]
					[/TR]
				[/TABLE]',
				'<table><tr><th>col1</th><th>col2</th></tr><tr><td>cell1</td><td>cell2</td></tr><tr><td rowspan="2">double height</td><td>x</td></tr><tr><td>x</td></tr><tr><td>cell1</td><td>cell2</td></tr><tr><td colspan="2">double width</td></tr></table>',
				array(
					'error' => array(
						array (
							'pos'      => 397,
							'msg'      => 'BBCode %1$s requires %2$s as parent',
							'params'   => array('TR', 'TABLE'),
							'bbcodeId' => 'TR'
						)
					)
				)
			),
			array(
				'[CODE=js]
					function foo()
					{
						alert("Hello world");
					}
				[/CODE]
				[CODE:123=php]
					function foo()
					{
						echo "[CODE]lol nested tags[/CODE]";
					}
				[/CODE:123]',
				'<pre class="brush:js">
					function foo()
					{
						alert("Hello world");
					}
				</pre>
				<pre class="brush:php">
					function foo()
					{
						echo "[CODE]lol nested tags[/CODE]";
					}
				</pre>'
			),
			array(
				'a[HR /]b',
				'a<hr>b'
			),
			array(
				'a[HR][/HR]b',
				'a<hr>b'
			),
			array(
				'a
				[HR /]
				b',
				'a<hr>b'
			),
		);
	}
}