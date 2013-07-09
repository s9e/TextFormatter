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
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>bold</b> x',
				'<rt xmlns:foo="urn:s9e:TextFormatter:foo">x <foo:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></foo:b> x</rt>',
				['prefix' => 'foo'],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <B>bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;B&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b Title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b Title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x<br/>y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br><st>&lt;br/&gt;</st></html:br>y</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			],
			[
				'x<br />y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br><st>&lt;br /&gt;</st></html:br>y</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			],
			[
				'x <input type=checkbox disabled checked /> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:input type="checkbox" disabled="disabled" checked="checked"><st>&lt;input type=checkbox disabled checked /&gt;</st></html:input> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('input');
					$configurator->HTMLElements->allowAttribute('input', 'type');
					$configurator->HTMLElements->allowAttribute('input', 'disabled');
					$configurator->HTMLElements->allowAttribute('input', 'checked');
				}
			],
			[
				'x <b title = "is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b title = "is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>...</b> y',
				'<rt>x <BOLD><st>&lt;b&gt;</st>...<et>&lt;/b&gt;</et></BOLD> y</rt>',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->aliasElement('b', 'bold');
					$configurator->tags->add('bold');
				}
			],
			[
				'x <a href="http://example.org">...</a> y',
				'<rt>x <URL url="http://example.org"><st>&lt;a href="http://example.org"&gt;</st>...<et>&lt;/a&gt;</et></URL> y</rt>',
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
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:span data-title="foo"><st>&lt;span title="foo"&gt;</st>...&lt;/b&gt; <html:div title="bar"><st>&lt;div title="bar"&gt;</st>...<et>&lt;/div&gt;</et></html:div> y</html:span></rt>',
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
					$configurator->stylesheet->outputMethod = 'html';
				}
			],
			[
				'Break: <br/> :)',
				'Break: <br/> :)',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
					$configurator->stylesheet->outputMethod = 'xml';
				}
			],
			[
				'Div: <div/> :)',
				'Div: <div></div> :)',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('div');
					$configurator->stylesheet->outputMethod = 'html';
				}
			],
			[
				'Div: <div/> :)',
				'Div: <div/> :)',
				[],
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('div');
					$configurator->stylesheet->outputMethod = 'xml';
				}
			],
		];
	}
}