<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLElements;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLElements\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLElements\Parser
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
				'x <b>bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>bold</b> x',
				'<r xmlns:foo="urn:s9e:TextFormatter:foo">x <foo:b><s>&lt;b&gt;</s>bold<e>&lt;/b&gt;</e></foo:b> x</r>',
				['prefix' => 'foo'],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><s>&lt;b title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <B>bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;B&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b Title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><s>&lt;b Title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x<br/>y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x<html:br><s>&lt;br/&gt;</s></html:br>y</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			],
			[
				'x<br />y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x<html:br><s>&lt;br /&gt;</s></html:br>y</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			],
			[
				'x <input disabled name=foo readonly /> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:input disabled="disabled" name="foo" readonly="readonly"><s>&lt;input disabled name=foo readonly /&gt;</s></html:input> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('input');
					$configurator->HTMLElements->allowAttribute('input', 'disabled');
					$configurator->HTMLElements->allowAttribute('input', 'name');
					$configurator->HTMLElements->allowAttribute('input', 'readonly');
				}
			],
			[
				'x <b title = "is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b title = "is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>...</b> y',
				'<r>x <BOLD><s>&lt;b&gt;</s>...<e>&lt;/b&gt;</e></BOLD> y</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->aliasElement('b', 'bold');
					$configurator->tags->add('bold');
				}
			],
			[
				'x <a href="http://example.org">...</a> y',
				'<r>x <URL url="http://example.org"><s>&lt;a href="http://example.org"&gt;</s>...<e>&lt;/a&gt;</e></URL> y</r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->aliasElement('a', 'url');
					$configurator->HTMLElements->aliasAttribute('a', 'href', 'url');

					$configurator->tags->add('URL')->attributes->add('url')->filterChain->append(
						$configurator->attributeFilters['#url']
					);
				}
			],
			[
				'x <span title="foo">...</b> <div title="bar">...</div> y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:span data-title="foo"><s>&lt;span title="foo"&gt;</s>...&lt;/b&gt; <html:div title="bar"><s>&lt;div title="bar"&gt;</s>...<e>&lt;/div&gt;</e></html:div> y</html:span></r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('span');
					$configurator->HTMLElements->allowAttribute('span', 'data-title');
					$configurator->HTMLElements->allowAttribute('span', 'title');
					$configurator->HTMLElements->allowElement('div');
					$configurator->HTMLElements->allowAttribute('div', 'data-title');
					$configurator->HTMLElements->allowAttribute('div', 'title');

					$configurator->HTMLElements->aliasAttribute('span', 'title', 'data-title');
				}
			],
			[
				'<img alt="">',
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:img alt=""><s>&lt;img alt=""&gt;</s></html:img></r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('img');
					$configurator->HTMLElements->allowAttribute('img', 'alt');
				}
			],
			[
				'<img data-crc32="123">',
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:img data-crc32="123"><s>&lt;img data-crc32="123"&gt;</s></html:img></r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('img');
					$configurator->HTMLElements->allowAttribute('img', 'data-crc32');
				}
			],
			[
				'<a href=http://example.org/>...</a>',
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:a href="http://example.org/"><s>&lt;a href=http://example.org/&gt;</s>...<e>&lt;/a&gt;</e></html:a></r>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('a');
					$configurator->HTMLElements->allowAttribute('a', 'href');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				['prefix' => 'foo'],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'x <b title="is bold">bold</b> x',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'Break: <br/> :)',
				'Break: <br> :)',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			],
			[
				'Div: <div/> :)',
				'Div: <div></div> :)',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('div');
				}
			],
		];
	}
}