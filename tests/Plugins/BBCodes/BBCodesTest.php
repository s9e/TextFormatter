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
		preg_match_all('/\\[([\\w*]+)/', $original, $matches);

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
				'x [b]bold[/b] y',
				'x <b>bold</b> y'
			),
			array(
				'x [B]BOLD[/b] y',
				'x <b>BOLD</b> y'
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
				'x [COLOR=red]is red[/COLOR] y',
				'x <span style="color:red">is red</span> y'
			),
			array(
				'x [COLOR=red]is [COLOR=green]green[/COLOR] and red[/COLOR] y',
				'x <span style="color:red">is <span style="color:green">green</span> and red</span> y'
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
				'x [i]italic[/I] y',
				'x <i>italic</i> y'
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
				'x [s]strikethrough[/s] y',
				'x <s>strikethrough</s> y'
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
				'x [url=foo://example.org]text[/url] y',
			),
			array(
				'x [url=foo://example.org]text[/url] y',
				'x <a href="foo://example.org">text</a> y',
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			),
		);
	}
}