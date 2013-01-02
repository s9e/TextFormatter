<?php

namespace s9e\TextFormatter\Tests\Plugins\Autolink;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autolink\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavascriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavascriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'Go to http://www.example.com/ for more info',
				'<rt>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</rt>'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<rt>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</rt>'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<rt>Go to <FOO url="http://www.example.com/">http://www.example.com/</FOO> for more info</rt>',
				array('tagName' => 'FOO')
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<rt>Go to <URL bar="http://www.example.com/">http://www.example.com/</URL> for more info</rt>',
				array('attrName' => 'bar')
			),
			array(
				'Go to foo://www.example.com/ for more info',
				'<pt>Go to foo://www.example.com/ for more info</pt>'
			),
			array(
				'Go to foo://www.example.com/ for more info',
				'<rt>Go to <URL url="foo://www.example.com/">foo://www.example.com/</URL> for more info</rt>',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			),
			array(
				'Go to http://www.example.com/. Like, now.',
				'<rt>Go to <URL url="http://www.example.com/">http://www.example.com/</URL>. Like, now.</rt>'
			),
			array(
				'Go to http://www.example.com/foo! Like, right now!',
				'<rt>Go to <URL url="http://www.example.com/foo">http://www.example.com/foo</URL>! Like, right now!</rt>'
			),
			array(
				'Go to http://www.example.com/?foo= for more info',
				'<rt>Go to <URL url="http://www.example.com/?foo=">http://www.example.com/?foo=</URL> for more info</rt>'
			),
			array(
				'Mars (http://en.wikipedia.org/wiki/Mars_(planet)) is the fourth planet from the Sun',
				'<rt>Mars (<URL url="http://en.wikipedia.org/wiki/Mars_%28planet%29">http://en.wikipedia.org/wiki/Mars_(planet)</URL>) is the fourth planet from the Sun</rt>'
			),
			array(
				'Mars (http://en.wikipedia.org/wiki/Mars) can mean many things',
				'<rt>Mars (<URL url="http://en.wikipedia.org/wiki/Mars">http://en.wikipedia.org/wiki/Mars</URL>) can mean many things</rt>'
			),
			array(
				/** @link http://area51.phpbb.com/phpBB/viewtopic.php?f=75&t=32142 */
				'http://www.xn--lyp-plada.com for http://www.älypää.com',
				'<rt><URL url="http://www.xn--lyp-plada.com">http://www.xn--lyp-plada.com</URL> for <URL url="http://www.xn--lyp-plada.com">http://www.älypää.com</URL></rt>',
				array(),
				null,
				'<rt><URL url="http://www.%C3%A4lyp%C3%A4%C3%A4.com">http://www.xn--lyp-plada.com</URL> for <URL url="http://www.%C3%A4lyp%C3%A4%C3%A4.com">http://www.älypää.com</URL></rt>'
			),
			array(
				'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen for http://en.wikipedia.org/wiki/Matti_Nykänen',
				'<rt><URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen</URL> for <URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nykänen</URL></rt>'
			),
			array(
				'Check this out http://en.wikipedia.org/wiki/♥',
				'<rt>Check this out <URL url="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</URL></rt>'
			),
			array(
				'Check those out: http://example.com/list.php?cat[]=1&cat[]=2',
				'<rt>Check those out: <URL url="http://example.com/list.php?cat[]=1&amp;cat[]=2">http://example.com/list.php?cat[]=1&amp;cat[]=2</URL></rt>'
			),
			array(
				'Check those out: http://example.com/list.php?cat[1a]=1&cat[1b]=2',
				'<rt>Check those out: <URL url="http://example.com/list.php?cat[1a]=1&amp;cat[1b]=2">http://example.com/list.php?cat[1a]=1&amp;cat[1b]=2</URL></rt>'
			),
			array(
				'[url=http://example.com]Non-existent URL tag[/url]',
				'<rt>[url=<URL url="http://example.com">http://example.com</URL>]Non-existent URL tag[/url]</rt>'
			),
			array(
				'Link in brackets: [http://example.com/foo] --',
				'<rt>Link in brackets: [<URL url="http://example.com/foo">http://example.com/foo</URL>] --</rt>'
			),
			array(
				'Link in brackets: [http://example.com/foo?a[]=1] --',
				'<rt>Link in brackets: [<URL url="http://example.com/foo?a[]=1">http://example.com/foo?a[]=1</URL>] --</rt>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				array('tagName' => 'FOO')
			),
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				array('attrName' => 'bar')
			),
			array(
				'Check this out http://en.wikipedia.org/wiki/♥',
				'Check this out <a href="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</a>'
			),
		);
	}
}