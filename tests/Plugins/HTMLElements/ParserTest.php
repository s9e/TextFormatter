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
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>bold</b> x',
				'<rt xmlns:foo="urn:s9e:TextFormatter:foo">x <foo:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></foo:b> x</rt>',
				['prefix' => 'foo'],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <B>bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;B&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b Title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b Title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'x<br/>y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br><st>&lt;br/&gt;</st></html:br>y</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
				}
			],
			[
				'x<br />y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br><st>&lt;br /&gt;</st></html:br>y</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
				}
			],
			[
				'x <input type=checkbox disabled checked /> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:input type="checkbox" disabled="disabled" checked="checked"><st>&lt;input type=checkbox disabled checked /&gt;</st></html:input> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('input');
					$constructor->HTMLElements->allowAttribute('input', 'type');
					$constructor->HTMLElements->allowAttribute('input', 'disabled');
					$constructor->HTMLElements->allowAttribute('input', 'checked');
				}
			],
			[
				'x <b title = "is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b title = "is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
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
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				['prefix' => 'foo'],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				}
			],
			[
				'x <b title="is bold">bold</b> x',
				'x <b title="is bold">bold</b> x',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				}
			],
			[
				'Break: <br/> :)',
				'Break: <br> :)',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
					$constructor->stylesheet->outputMethod = 'html';
				}
			],
			[
				'Break: <br/> :)',
				'Break: <br/> :)',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
					$constructor->stylesheet->outputMethod = 'xml';
				}
			],
			[
				'Div: <div/> :)',
				'Div: <div></div> :)',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('div');
					$constructor->stylesheet->outputMethod = 'html';
				}
			],
			[
				'Div: <div/> :)',
				'Div: <div/> :)',
				[],
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('div');
					$constructor->stylesheet->outputMethod = 'xml';
				}
			],
		];
	}
}