<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLElements;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLElements\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLElements\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'x <b>bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <b>bold</b> x',
				'<rt xmlns:foo="urn:s9e:TextFormatter:foo">x <foo:b><st>&lt;b&gt;</st>bold<et>&lt;/b&gt;</et></foo:b> x</rt>',
				array('prefix' => 'foo'),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				},
			),
			array(
				'x <b title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;b title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <B>bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b><st>&lt;B&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <b Title="is bold">bold</b> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><st>&lt;b Title="is bold"&gt;</st>bold<et>&lt;/b&gt;</et></html:b> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				},
			),
			array(
				'x<br/>y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br>&lt;br/&gt;</html:br>y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
				},
			),
			array(
				'x<br />y',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x<html:br>&lt;br /&gt;</html:br>y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('br');
				},
			),
			array(
				'x <input type=checkbox disabled checked /> x',
				'<rt xmlns:html="urn:s9e:TextFormatter:html">x <html:input type="checkbox" disabled="disabled" checked="checked">&lt;input type=checkbox disabled checked /&gt;</html:input> x</rt>',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('input');
					$constructor->HTMLElements->allowAttribute('input', 'type');
					$constructor->HTMLElements->allowAttribute('input', 'disabled');
					$constructor->HTMLElements->allowAttribute('input', 'checked');
				},
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				array('prefix' => 'foo'),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
				},
			),
			array(
				'x <b title="is bold">bold</b> x',
				'x <b title="is bold">bold</b> x',
				array(),
				function ($constructor)
				{
					$constructor->HTMLElements->allowElement('b');
					$constructor->HTMLElements->allowAttribute('b', 'title');
				},
			),
		);
	}
}