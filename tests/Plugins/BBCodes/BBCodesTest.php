<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\BBCodes;
use s9e\TextFormatter\Tests\Test;

/**
* @coversNothing
*/
class BBCodesTest extends Test
{
	/**
	* @testdox BBCodes from repository.xml render nicely
	* @dataProvider getPredefinedBBCodesTests
	*/
	public function test($original, $expected, $setup = null)
	{
		$configurator = new Configurator;

		if (isset($setup))
		{
			call_user_func($setup, $configurator);
		}

		// Capture the names of the BBCodes used
		preg_match_all('/\\[([*\\w]+)/', $original, $matches);

		foreach ($matches[1] as $bbcodeName)
		{
			if (!isset($configurator->BBCodes[$bbcodeName]))
			{
				$configurator->BBCodes->addFromRepository($bbcodeName);
			}
		}

		$configurator->addHTML5Rules();

		$xml  = $configurator->getParser()->parse($original);
		$html = $configurator->getRenderer()->render($xml);

		$this->assertSame(
			$expected,
			$html
		);
	}

	public function getPredefinedBBCodesTests()
	{
		return array(
			array(
				'[acronym="foobar"]F.B[/acronym]',
				'<acronym title="foobar">F.B</acronym>'
			),
			array(
				'[acronym="\'\"foobar\"\'"]F.B[/acronym]',
				'<acronym title="\'&quot;foobar&quot;\'">F.B</acronym>'
			),
			array(
				'[align=center]...[/align]',
				'<div style="text-align:center">...</div>'
			),
			array(
				'[align=;color:red]...[/align]',
				'[align=;color:red]...[/align]'
			),
			array(
				'x [b]bold[/b] y',
				'x <b>bold</b> y'
			),
			array(
				'x [B]BOLD[/b] y',
				'x <b>BOLD</b> y'
			),
			array(
				'x [background=yellow]color me[/background] y',
				'x <span style="background-color:yellow">color me</span> y'
			),
			array(
				'x [C][b]not bold[/b][/C] y',
				'x <code class="inline">[b]not bold[/b]</code> y'
			),
			array(
				'x [C:123][C][b]not bold[/b][/C][/C:123] y',
				'x <code class="inline">[C][b]not bold[/b][/C]</code> y'
			),
			array(
				'[center]...[/center]',
				'<div style="text-align:center">...</div>'
			),
			array(
				'[code]echo "Hello world";[/code]',
				'<pre><code class="">echo "Hello world";</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>',
			),
			array(
				'[code=html]<b>Hello world</b>[/code]',
				'<pre><code class="html">&lt;b&gt;Hello world&lt;/b&gt;</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>',
			),
			array(
				'[code]alert("first");[/code][code]alert("second");[/code]',
				'<pre><code class="">alert("first");</code></pre><pre><code class="">alert("second");</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/default.min.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js"></script>'
			),
			array(
				'[code=php]echo "Hello world";[/code]',
				'<pre><code class="php">echo "Hello world";</code></pre><script>var l=document.createElement("link");l.type="text/css";l.rel="stylesheet";l.href="highlight.css";document.getElementsByTagName("head")[0].appendChild(l)</script><script onload="hljs.initHighlighting()" src="highlight.js"></script>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('CODE', 'default', array(
						'scriptUrl'     => 'highlight.js',
						'stylesheetUrl' => 'highlight.css'
					));
				}
			),
			array(
				'x [COLOR=red]is red[/COLOR] y',
				'x <span style="color:red">is red</span> y'
			),
			array(
				'x [COLOR=red]is [COLOR=green]green[/COLOR] and red[/COLOR] y',
				'x <span style="color:red">is <span style="color:green">green</span> and red</span> y'
			),
			array(
				'our [del]great [/del]leader',
				'our <del>great </del>leader'
			),
			array(
				"[dl]
					[dt]Hacker
					[dd]a clever programmer
					[dt]Nerd
					[dd]technically bright but socially [s]inept[/s] awesome person
				[/dl]",
				'<dl><dt>Hacker</dt><dd>a clever programmer</dd><dt>Nerd</dt><dd>technically bright but socially <s>inept</s> awesome person</dd></dl>'
			),
			array(
				'Putting the EM in [em]em[/em]phasis',
				'Putting the EM in <em>em</em>phasis'
			),
			array(
				'x [EMAIL]test@example.org[/EMAIL] y',
				'x <a href="mailto:test@example.org">test@example.org</a> y'
			),
			array(
				'x [EMAIL=test@example.org]email[/EMAIL] y',
				'x <a href="mailto:test@example.org">email</a> y'
			),
			array(
				'x [FLASH=600,400]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="600" height="400"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="600" height="400" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y',
			),
			array(
				'x [FLASH]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="80" height="60"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="80" height="60" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y',
			),
			array(
				'x [FLASH=10000,10000]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="1920" height="1080"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="1920" height="1080" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y'
			),
			array(
				'x [FLASH=10000,10000]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="10000" height="10000"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="10000" height="10000" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('FLASH', 'default', array(
						'maxHeight' => 10000,
						'maxWidth'  => 10000
					));
				}
			),
			array(
				'x [FLASH=0,0]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="0" height="0"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="0" height="0" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y'
			),
			array(
				'x [FLASH=0,0]http://example.org/foo.swf[/FLASH] y',
				'x <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="80" height="60"><param name="movie" value="http://example.org/foo.swf"><param name="quality" value="high"><param name="wmode" value="opaque"><param name="play" value="false"><param name="loop" value="false"><param name="allowScriptAccess" value="never"><param name="allowNetworking" value="internal"><embed src="http://example.org/foo.swf" quality="high" width="80" height="60" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed></object> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('FLASH', 'default', array(
						'minHeight' => 60,
						'minWidth'  => 80
					));
				}
			),
			array(
				'x [font=Arial]Arial[/font] y',
				'x <span style="font-family:Arial">Arial</span> y'
			),
			array(
				'x [i]italic[/I] y',
				'x <i>italic</i> y'
			),
			array(
				"[h1]h1[/h1]\n" .
				"[h2]h2[/h2]\n" .
				"[h3]h3[/h3]\n" .
				"[h4]h4[/h4]\n" .
				"[h5]h5[/h5]\n" .
				"[h6]h6[/h6]\n",
				'<h1>h1</h1><h2>h2</h2><h3>h3</h3><h4>h4</h4><h5>h5</h5><h6>h6</h6>'
			),
			array(
				'[h1]h1 [b]b[/b][/h1]',
				'<h1>h1 <b>b</b></h1>'
			),
			array(
				'[h1]h1 [quote]...[/quote][/h1]',
				'<h1>h1 [quote]...[/quote]</h1>'
			),
			array(
				"xxxx\n" .
				"[hr]\n" .
				"yyyyy",
				'xxxx<hr>yyyyy'
			),
			array(
				'x [B]bold [i]italic[/b][/I] y',
				'x <b>bold <i>italic</i></b><i></i> y'
			),
			array(
				'x [img]http://example.org/foo.png[/img] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
			array(
				'x [img=http://example.org/foo.png] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
			array(
				'x [img=http://example.org/foo.png /] y',
				'x <img src="http://example.org/foo.png" title="" alt=""> y'
			),
			array(
				'our [ins]great [/ins]leader',
				'our <ins>great </ins>leader'
			),
			array(
				'[justify]...[/justify]',
				'<div style="text-align:justify">...</div>'
			),
			array(
				'[left]...[/left]',
				'<div style="text-align:left">...</div>'
			),
			array(
				'[LIST][*]one[*]two[/LIST]',
				'<ul style="list-style-type:disc"><li>one</li><li>two</li></ul>'
			),
			array(
				'[LIST]
					[*]one
					[*]two
				[/LIST]',
				'<ul style="list-style-type:disc"><li>one</li><li>two</li></ul>'
			),
			array(
				'[LIST]
					[*][LIST]
						[*]one.one
						[*]one.two
					[/LIST]

					[*]two
				[/LIST]',
				'<ul style="list-style-type:disc"><li><ul style="list-style-type:disc"><li>one.one</li><li>one.two</li></ul></li><li>two</li></ul>'
			),
			array(
				'[LIST=1][*]one[*]two[/LIST]',
				'<ol style="list-style-type:decimal"><li>one</li><li>two</li></ol>'
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
				'[LIST=i][*]one[*]two[/LIST]',
				'<ol style="list-style-type:lower-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=I][*]one[*]two[/LIST]',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li></ol>'
			),
			array(
				'[LIST=square][*]one[*]two[/LIST]',
				'<ul style="list-style-type:square"><li>one</li><li>two</li></ul>'
			),
			array(
				'[LIST=";zoom:100"][*]one[*]two[/LIST]',
				'<ul style="list-style-type:disc"><li>one</li><li>two</li></ul>'
			),
			array(
				'[NOPARSE][b]no bold[/b][/NOPARSE] [b]bold[/b]',
				'[b]no bold[/b] <b>bold</b>'
			),
			array(
				"[NOPARSE]still converts new\nlines[/NOPARSE]",
				"still converts new<br>\nlines"
			),
			array(
				'[QUOTE]...text...[/QUOTE]',
				'<blockquote class="uncited"><div>...text...</div></blockquote>'
			),
			array(
				'[QUOTE=namehere]...text...[/QUOTE]',
				'<blockquote><div><cite>namehere wrote:</cite>...text...</div></blockquote>'
			),
			array(
				'[QUOTE=namehere]...text...[/QUOTE]',
				'<blockquote><div><cite>namehere ha escrit:</cite>...text...</div></blockquote>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('QUOTE', 'default', array(
						'authorStr' => '<xsl:value-of select="@author"/> ha escrit:'
					));
				}
			),
			array(
				"my quote:\n" .
				"\n" .
				"[QUOTE]...text...[/QUOTE]\n" .
				"\n" .
				"follow-up",
				'my quote:<blockquote class="uncited"><div>...text...</div></blockquote>follow-up'
			),
			array(
				"my quote:\n" .
				"\n" .
				"\n" .
				"[QUOTE]...text...[/QUOTE]\n" .
				"\n" .
				"\n" .
				"follow-up",

				"my quote:<br>\n" .
				"<blockquote class=\"uncited\"><div>...text...</div></blockquote><br>\n" .
				"follow-up"
			),
			array(
				'[right]...[/right]',
				'<div style="text-align:right">...</div>'
			),
			array(
				'x [s]strikethrough[/s] y',
				'x <s>strikethrough</s> y'
			),
			array(
				"Spoiler ahead!\n" .
				"[spoiler]Now you're spoiled[/spoiler]",
				'Spoiler ahead!<div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: </span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>'
			),
			array(
				"Spoiler ahead!\n" .
				'[spoiler="your spoilage status"]Now you\'re spoiled[/spoiler]',
				'Spoiler ahead!<div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: your spoilage status</span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>'
			),
			array(
				"Spoiler ahead!\n" .
				"[spoiler][spoiler='Last chance']Now you're spoiled[/spoiler][/spoiler]",
				'Spoiler ahead!<div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: </span></div><div class="spoiler-content" style="display:none"><div class="spoiler"><div class="spoiler-header"><input type="button" value="Show" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Hide\'}else{s.display=\'none\';this.value=\'Show\'}"><span class="spoiler-title">Spoiler: Last chance</span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div></div></div>'
			),
			array(
				"Spoiler ahead!\n" .
				"[spoiler]Now you're spoiled[/spoiler]",
				'Spoiler ahead!<div class="spoiler"><div class="spoiler-header"><input type="button" value="Montrer" onclick="var s=this.parentNode.nextSibling.style;if(s.display!=\'\'){s.display=\'\';this.value=\'Cacher\'}else{s.display=\'none\';this.value=\'Montrer\'}"><span class="spoiler-title">Spoiler : </span></div><div class="spoiler-content" style="display:none">Now you\'re spoiled</div></div>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('SPOILER', 'default', array(
						'showStr'    => 'Montrer',
						'hideStr'    => 'Cacher',
						'spoilerStr' => 'Spoiler :'
					));
				}
			),
			array(
				'Some [strong]strong[/strong] words',
				'Some <strong>strong</strong> words'
			),
			array(
				'x [sub]sub[/sub] y',
				'x <sub>sub</sub> y'
			),
			array(
				'x [sup]sup[/sup] y',
				'x <sup>sup</sup> y'
			),
			array(
				'x [u]underline[/u] y',
				'x <u>underline</u> y'
			),
			array(
				'x [url]http://example.org[/url] y',
				'x <a href="http://example.org">http://example.org</a> y'
			),
			array(
				'x [url]https://example.org[/url] y',
				'x <a href="https://example.org">https://example.org</a> y'
			),
			array(
				'x [url=http://example.org]text[/url] y',
				'x <a href="http://example.org">text</a> y'
			),
			array(
				'x [url=http://example.org title="my title"]text[/url] y',
				'x <a href="http://example.org" title="my title">text</a> y'
			),
			array(
				'x [url=http://example.org][url=http://example.org]text[/url][/url] y',
				'x <a href="http://example.org">[url=http://example.org]text</a>[/url] y'
			),
			array(
				'x [url:123=http://example.org][url=http://example.org]text[/url][/url:123] y',
				'x <a href="http://example.org">[url=http://example.org]text[/url]</a> y'
			),
			array(
				'x [url=http://example.org][EMAIL]test@example.org[/EMAIL][/url] y',
				'x <a href="http://example.org">[EMAIL]test@example.org[/EMAIL]</a> y'
			),
			array(
				'x [url=javascript:foo]text[/url] y',
				'x [url=javascript:foo]text[/url] y'
			),
			array(
				'x [url=http://example.org]text[/url] [url=http://evil.example.org]evil[/url] y',
				'x <a href="http://example.org">text</a> <a href="http://evil.example.org">evil</a> y'
			),
			array(
				'x [url=http://example.org]text[/url] [url=http://evil.example.org]evil[/url] y',
				'x <a href="http://example.org">text</a> [url=http://evil.example.org]evil[/url] y',
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.org');
				}
			),
			array(
				'x [url=//example.org]text[/url] y',
				'x <a href="//example.org">text</a> y'
			),
			array(
				'x [url=//example.org]text[/url] y',
				'x [url=//example.org]text[/url] y',
				function ($configurator)
				{
					$configurator->urlConfig->requireScheme();
				}
			),
			array(
				'x [url=foo://example.org]text[/url] y',
				'x [url=foo://example.org]text[/url] y'
			),
			array(
				'x [url=foo://example.org]text[/url] y',
				'x <a href="foo://example.org">text</a> y',
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			),
			array(
				'x [url=http://example.org][flash=10,20]http://example.org/foo.swf[/flash][/url] y',
				'x <a href="http://example.org">[flash=10,20]http://example.org/foo.swf[/flash]</a> y'
			),
			array(
				'x [url=http://example.org][img]http://example.org/foo.png[/img][/url] y',
				'x <a href="http://example.org"><img src="http://example.org/foo.png" title="" alt=""></a> y'
			),
			array(
				'x [url=http://example.org][img]http://example.org/foo.png[/img][/url] y',
				'x <a href="http://example.org">[img]http://example.org/foo.png[/img]</a> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('url');
					$configurator->BBCodes->addFromRepository('img');
					$configurator->tags['url']->rules->denyDescendant('img');
				}
			),
			array(
				'x [var]var[/var] y',
				'x <var>var</var> y'
			),
			array(
				'[var]x[sub][var]i[/var][/sub][/var]',
				'<var>x<sub><var>i</var></sub></var>'
			),
			array(
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" frameborder="0" allowfullscreen=""></iframe>'
			),
			array(
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" frameborder="0" allowfullscreen=""></iframe>'
			),
			array(
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<iframe width="853" height="505" src="http://www.youtube.com/embed/-cEzsCAzTak" frameborder="0" allowfullscreen=""></iframe>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('youtube', 'default', array(
						'width'  => 853,
						'height' => 505
					));
				}
			),
		);
	}
}