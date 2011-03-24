<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PredefinedBBCodes,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\PredefinedTags
* @covers s9e\Toolkit\TextFormatter\PredefinedBBCodes
*/
class PredefinedBBCodesTest extends Test
{
	/**
	* @expectedException InvalidArgumentException UNKNOWN
	*/
	public function testAddPredefinedBBCodeThrowsAnExceptionOnUnknownBBCodes()
	{
		$cb = new ConfigBuilder;
		$cb->BBCodes->addPredefinedBBCode('UNKNOWN');
	}

	public function testGetCODEstx()
	{
		$cb = new ConfigBuilder;
		$cb->BBCodes->addPredefinedBBCode('CODE');

		$xml = $cb->getParser()->parse('[code=php]//[/code][code=php]//[/code][code=html][/code]');

		$this->assertEquals(
			array('php', 'html'),
			PredefinedBBCodes::getCODEstx($xml)
		);
	}

	/**
	* @dataProvider provider
	*/
	public function testPredefinedBBCodes($text, $expected, $expectedLog = array(), $args = array())
	{
		preg_match('#(?<=\\[)[a-z_0-9]+#i', $text, $m);
		array_unshift($args, $m[0]);

		$cb = new ConfigBuilder;
		call_user_func_array(array($cb->BBCodes, 'addPredefinedBBCode'), $args);

		$parser = $cb->getParser();
		$xml = $parser->parse($text);

		$actual = $cb->getRenderer()->render($xml);
		$this->assertSame($expected, $actual);

		if (isset($expectedLog))
		{
			$actualLog = $parser->getLog();

			foreach (array_keys($expectedLog) as $type)
			{
				$this->assertArrayHasKey($type, $actualLog);
				$this->assertEquals($expectedLog[$type], $actualLog[$type]);
			}
		}
	}

	public function provider()
	{
		return array(
		array(
				'[B]bold [B]bolder[/B][/B]',
				'<b>bold <b>bolder</b></b>'
			),
			array(
				'[I]italic [I]italicer[/I][/I]',
				'<i>italic <i>italicer</i></i>'
			),
			array(
				'[U]underlined [U]well, still underlined[/U][/U]',
				'<span style="text-decoration:underline">underlined <span style="text-decoration:underline">well, still underlined</span></span>'
			),
			array(
				'[S]strike [S]still striked[/S][/S]',
				'<span style="text-decoration:line-through">strike <span style="text-decoration:line-through">still striked</span></span>'
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
				// [IMG] tag is automatically closed if it doesn't use its content as URL
				'[IMG=http://www.example.org/img.png]TEXT[/IMG]',
				'<img src="http://www.example.org/img.png">TEXT[/IMG]'
			),
			array(
				'[IMG:1=http://www.example.org/img.png][/IMG:1]',
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
				'[GOOGLEVIDEO]http://video.google.com/videoplay?docid=-4381488634998231167[/GOOGLEVIDEO]',
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
						array(
							'pos'     => 397,
							'msg'     => 'Tag %1$s requires %2$s as parent',
							'params'  => array('TR', 'TABLE'),
							'tagName' => 'TR'
						)
					)
				)
			),
			array(
				'[TABLE]
					[COL]
					[COL align=right]
					[TR]
						[TD]cell1[/TD]
						[TD]cell2[/TD]
					[/TR]
				',
				'<table><col><col style="text-align:right"><tr><td>cell1</td><td>cell2</td></tr></table>'
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
			array(
				'[QUOTE]this is a quote[/QUOTE]',
				'<blockquote class="uncited"><div>this is a quote</div></blockquote>'
			),
			array(
				'[QUOTE="Author"]this is a quote[/QUOTE]',
				'<blockquote><div><cite>Author wrote:</cite>this is a quote</div></blockquote>'
			),
			// change the "%s wrote:" string
			array(
				'[QUOTE="Author"]this is a quote[/QUOTE]',
				'<blockquote><div><cite>Author said:</cite>this is a quote</div></blockquote>',
				null,
				array(99, '%s said:')
			),
			// limit quote nesting to 2
			array(
				'[QUOTE][QUOTE][QUOTE="Author"]this is a quote[/QUOTE][/QUOTE][/QUOTE]',
				'<blockquote class="uncited"><div><blockquote class="uncited"><div>[QUOTE="Author"]this is a quote</div></blockquote></div></blockquote>[/QUOTE]',
				null,
				array(2)
			),
			array(
				'[EMAIL]admin@example.org[/EMAIL]',
				'<a href="javascript:" style="unicode-bidi:bidi-override;direction:rtl" onfocus="this.onmouseover()" onmouseover="this.href=\'gro.elpmaxe@nimda:otliam\'.split(\'\').reverse().join(\'\')">gro.elpmaxe@nimda</a>'
			),
			array(
				'[EMAIL=admin@example.org]email me![/EMAIL]',
				'<a href="javascript:" style="unicode-bidi:bidi-override;direction:rtl" onfocus="this.onmouseover()" onmouseover="this.href=\'gro.elpmaxe@nimda:otliam\'.split(\'\').reverse().join(\'\')">!em liame</a>'
			),
			array(
				'[EMAIL=admin@example.org subject="hello admin@example.org!"]email me![/EMAIL]',
				'<a href="javascript:" style="unicode-bidi:bidi-override;direction:rtl" onfocus="this.onmouseover()" onmouseover="this.href=\'12%gro.elpmaxe04%nimda02%olleh=tcejbus?gro.elpmaxe@nimda:otliam\'.split(\'\').reverse().join(\'\')">!em liame</a>'
			),
			array(
				'[JUSTIN]http://www.justin.tv/justin[/JUSTIN]',
				'<object type="application/x-shockwave-flash" height="300" width="400" data="http://www.justin.tv/widgets/live_embed_player.swf?channel=justin" bgcolor="#000000"><param name="allowFullScreen" value="true"><param name="allowScriptAccess" value="always"><param name="allowNetworking" value="all"><param name="movie" value="http://www.justin.tv/widgets/live_embed_player.swf"><param name="flashvars" value="channel=justin&amp;auto_play=false"></object>'
			),
			array(
				'[LOCALTIME]2005/09/17 12:55:09 PST[/LOCALTIME]',
				'<span class="localtime" title="2005/09/17 12:55:09 PST"><script type="text/javascript">document.write(new Date(1126990509*1000).toLocaleString())</script><noscript>2005/09/17 12:55:09 PST</noscript></span>'
			),
			array(
				'[LOCALTIME]LOL HAX?[/LOCALTIME]',
				'[LOCALTIME]LOL HAX?[/LOCALTIME]',
				array(
					'error' => array(
						array(
							'pos'       => 0,
							'msg'       => "Invalid attribute '%s'",
							'params'    => array('localtime'),
							'tagName'  => 'LOCALTIME',
							'attrName' => 'localtime'
						),
						array(
							'pos'       => 0,
							'msg'       => "Missing attribute '%s'",
							'params'    => array('localtime'),
							'tagName'  => 'LOCALTIME'
						)
					)
				)
			),
			array(
				'[SPOILER]Spoiler content[/SPOILER]',
				'<div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: </span></div><div class="spoiler-content" style="display:none">Spoiler content</div></div>'
			),
			array(
				'[SPOILER="Spoiler title"]Spoiler content[/SPOILER]',
				'<div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: Spoiler title</span></div><div class="spoiler-content" style="display:none">Spoiler content</div></div>'
			),
			array(
				'[COLOR=red]Red stuff[/COLOR]',
				'<span style="color:red">Red stuff</span>'
			),
			array(
				'[COLOR=#ff0]Yellow stuff[/COLOR]',
				'<span style="color:#ff0">Yellow stuff</span>'
			),
			array(
				'[SIZE=50]Small[/SIZE]',
				'<span style="font-size:50%">Small</span>'
			),
			array(
				'[SIZE=250]Too big[/SIZE]',
				'<span style="font-size:200%">Too big</span>',
				array(
					'warning' => array(
						array(
							'pos'      => 0,
							'msg'      => 'Attribute \'%1$s\' outside of range, value adjusted down to %2$d',
							'params'   => array(200),
							'tagName'  => 'SIZE',
							'attrName' => 'size'
						)
					)
				)
			),
			array(
				'[SIZE=200]big [SIZE=200]bigger?[/SIZE][/SIZE]',
				'<span style="font-size:200%">big [SIZE=200]bigger?</span>[/SIZE]'
			),
			array(
				'[BLIP]4854905[/BLIP]',
				'<embed src="http://blip.tv/play/4854905" type="application/x-shockwave-flash" width="480" height="300" allowscriptaccess="always" allowfullscreen="true"></embed>'
			),
			array(
				'[BLIP]http://blip.tv/file/4854905/[/BLIP]',
				'<embed src="http://blip.tv/play/4854905" type="application/x-shockwave-flash" width="480" height="300" allowscriptaccess="always" allowfullscreen="true"></embed>'
			),
			array(
				'[VIMEO]20800127[/VIMEO]',
				'<iframe src="http://player.vimeo.com/video/20800127" width="400" height="225" frameborder="0"></iframe>'
			),
			array(
				'[VIMEO]http://vimeo.com/20800127[/VIMEO]',
				'<iframe src="http://player.vimeo.com/video/20800127" width="400" height="225" frameborder="0"></iframe>'
			),
			array(
				'[DAILYMOTION]xf633p[/DAILYMOTION]',
				'<object width="480" height="270"><param name="movie" value="http://www.dailymotion.com/swf/video/xf633p"><param name="allowFullScreen" value="true"><param name="allowScriptAccess" value="always"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/video/xf633p" width="480" height="270" allowfullscreen="true" allowscriptaccess="always"></embed></object>'
			),
			array(
				'[DAILYMOTION]http://www.dailymotion.com/video/xf633p_dailymotion-cloud_tech[/DAILYMOTION]',
				'<object width="480" height="270"><param name="movie" value="http://www.dailymotion.com/swf/video/xf633p"><param name="allowFullScreen" value="true"><param name="allowScriptAccess" value="always"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/video/xf633p" width="480" height="270" allowfullscreen="true" allowscriptaccess="always"></embed></object>'
			),
			array(
				'[INS]ins[/INS]',
				'<ins>ins</ins>'
			),
			array(
				'[DEL]del[/DEL]',
				'<del>del</del>'
			),
			array(
				'[FLASH=200,100]http://www.adobe.com/swf/software/flash/about/flashAbout_info_small.swf[/FLASH]',
				'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="200" height="100"><param name="movie" value="http://www.adobe.com/swf/software/flash/about/flashAbout_info_small.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://www.adobe.com/swf/software/flash/about/flashAbout_info_small.swf" quality="high" width="200" height="100" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object>'
			),
			array(
				'[EM]text[/EM]',
				'<em>text</em>'
			),
			array(
				'[STRONG]text[/STRONG]',
				'<strong>text</strong>'
			)
		);
	}
}