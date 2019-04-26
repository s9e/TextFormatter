<?php

namespace s9e\TextFormatter\Tests\Plugins\Autolink;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autolink\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</r>'
			],
			[
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</r>'
			],
			[
				'Go to http://www.example.com/ for more info',
				'<r>Go to <FOO url="http://www.example.com/">http://www.example.com/</FOO> for more info</r>',
				['tagName' => 'FOO']
			],
			[
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL bar="http://www.example.com/">http://www.example.com/</URL> for more info</r>',
				['attrName' => 'bar']
			],
			[
				'Go to foo://www.example.com/ for more info',
				'<t>Go to foo://www.example.com/ for more info</t>'
			],
			[
				'Go to foo://www.example.com/ for more info',
				'<r>Go to <URL url="foo://www.example.com/">foo://www.example.com/</URL> for more info</r>',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			],
			[
				'Go to http://www.example.com/. Like, now.',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL>. Like, now.</r>'
			],
			[
				'Go to http://www.example.com/foo! Like, right now!',
				'<r>Go to <URL url="http://www.example.com/foo">http://www.example.com/foo</URL>! Like, right now!</r>'
			],
			[
				'Go to http://www.example.com/?foo= for more info',
				'<r>Go to <URL url="http://www.example.com/?foo=">http://www.example.com/?foo=</URL> for more info</r>'
			],
			[
				'Mars (http://en.wikipedia.org/wiki/Mars_(planet)) is the fourth planet from the Sun',
				'<r>Mars (<URL url="http://en.wikipedia.org/wiki/Mars_%28planet%29">http://en.wikipedia.org/wiki/Mars_(planet)</URL>) is the fourth planet from the Sun</r>'
			],
			[
				'Mars (http://en.wikipedia.org/wiki/Mars) can mean many things',
				'<r>Mars (<URL url="http://en.wikipedia.org/wiki/Mars">http://en.wikipedia.org/wiki/Mars</URL>) can mean many things</r>'
			],
			[
				/** @link http://area51.phpbb.com/phpBB/viewtopic.php?f=75&t=32142 */
				'http://www.xn--lyp-plada.com for http://www.älypää.com',
				'<r><URL url="http://www.xn--lyp-plada.com">http://www.xn--lyp-plada.com</URL> for <URL url="http://www.xn--lyp-plada.com">http://www.älypää.com</URL></r>',
				[],
				function ()
				{
					if (!function_exists('idn_to_ascii'))
					{
						$this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			],
			[
				'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen for http://en.wikipedia.org/wiki/Matti_Nykänen',
				'<r><URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen</URL> for <URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nykänen</URL></r>'
			],
			[
				'Check this out http://en.wikipedia.org/wiki/♥',
				'<r>Check this out <URL url="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</URL></r>'
			],
			[
				'Check those out: http://example.com/list.php?cat[]=1&cat[]=2',
				'<r>Check those out: <URL url="http://example.com/list.php?cat%5B%5D=1&amp;cat%5B%5D=2">http://example.com/list.php?cat[]=1&amp;cat[]=2</URL></r>'
			],
			[
				'Check those out: http://example.com/list.php?cat[1a]=1&cat[1b]=2',
				'<r>Check those out: <URL url="http://example.com/list.php?cat%5B1a%5D=1&amp;cat%5B1b%5D=2">http://example.com/list.php?cat[1a]=1&amp;cat[1b]=2</URL></r>'
			],
			[
				'[url=http://example.com]Non-existent URL tag[/url]',
				'<r>[url=<URL url="http://example.com">http://example.com</URL>]Non-existent URL tag[/url]</r>'
			],
			[
				'Link in brackets: [http://example.com/foo] --',
				'<r>Link in brackets: [<URL url="http://example.com/foo">http://example.com/foo</URL>] --</r>'
			],
			[
				'Link in brackets: [http://example.com/foo?a[]=1] --',
				'<r>Link in brackets: [<URL url="http://example.com/foo?a%5B%5D=1">http://example.com/foo?a[]=1</URL>] --</r>'
			],
			[
				'Link in angle brackets: <http://example.com/foo>',
				'<r>Link in angle brackets: &lt;<URL url="http://example.com/foo">http://example.com/foo</URL>&gt;</r>'
			],
			[
				'http://example.com/foo~ http://example.com/~foo',
				'<r><URL url="http://example.com/foo">http://example.com/foo</URL>~ <URL url="http://example.com/~foo">http://example.com/~foo</URL></r>'
			],
			[
				'~~http://example.com/~foo~~',
				'<r><p><DEL><s>~~</s><URL url="http://example.com/~foo">http://example.com/~foo</URL><e>~~</e></DEL></p></r>',
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
			[
				'WWW address: www.example.org',
				'<r>WWW address: <URL url="http://www.example.org">www.example.org</URL></r>',
				['matchWww' => true]
			],
			[
				'WWW ADDRESS: WWW.EXAMPLE.ORG',
				'<r>WWW ADDRESS: <URL url="http://WWW.EXAMPLE.ORG">WWW.EXAMPLE.ORG</URL></r>',
				['matchWww' => true]
			],
			[
				'WWW address: www.example.org ...',
				'<r>WWW address: <URL url="http://www.example.org">www.example.org</URL> ...</r>',
				['matchWww' => true]
			],
			[
				'Not a WWW address: Awww...',
				'<t>Not a WWW address: Awww...</t>',
				['matchWww' => true]
			],
			[
				'Not a valid URL: foohttp://example.org',
				'<t>Not a valid URL: foohttp://example.org</t>'
			],
			[
				"... http://__ ...",
				'<t>... http://__ ...</t>'
			],
			[
				'... www.__ ..',
				'<t>... www.__ ..</t>',
				['matchWww' => true]
			],
			[
				'http://example.org http://example.com',
				'<r><URL url="http://example.org">http://example.org</URL> <PREG_5DE80F89>http://example.com</PREG_5DE80F89></r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace('#http://example\\.com#', '...');
				}
			],
			[
				'“极·致·轻”，没有如果，你就是英雄！车库源码（http://src.cool）与您共同进步！',
				'<r>“极·致·轻”，没有如果，你就是英雄！车库源码（<URL url="http://src.cool">http://src.cool</URL>）与您共同进步！</r>'
			],
			[
				'http://example.org',
				'<r><URL url="http://example.org"><X>http://example.org</X></URL></r>',
				[],
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags['URL']->filterChain->append(__CLASS__ . '::addContentTag')
						->resetParameters()
						->addParameterByName('tag')
						->addParameterByName('parser')
						->setJS("
							function (tag)
							{
								var tagPos = tag.getPos(),
									tagLen = tag.getEndTag().getPos() - tagPos;
								addSelfClosingTag('X', tagPos, tagLen, tag.getSortPriority());
							}
						");
				}
			],
		];
	}

	public static function addContentTag($tag, $parser)
	{
		$tagPos = $tag->getPos();
		$tagLen = $tag->getEndTag()->getPos() - $tagPos;
		$parser->addSelfClosingTag('X', $tagPos, $tagLen, $tag->getSortPriority());
	}

	public function getRenderingTests()
	{
		return [
			[
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info'
			],
			[
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				['tagName' => 'FOO']
			],
			[
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				['attrName' => 'bar']
			],
			[
				'Check this out http://en.wikipedia.org/wiki/♥',
				'Check this out <a href="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</a>'
			],
		];
	}
}