<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\RawHTMLParser
*/
class RawHTMLParserTest extends Test
{
	public function testCanParseAPairOfTagsWithNoAttributes()
	{
		$this->cb->RawHTML->allowElement('b');

		$this->assertParsing(
			'Hello <b>world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b><st>&lt;b&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	/**
	* @testdox Can parse self-closing tags with no attributes
	*/
	public function testCanParseSelfClosingTagsWithNoAttributes()
	{
		$this->cb->RawHTML->allowElement('br');

		$this->assertParsing(
			'a<br/>b',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">a<html:br>&lt;br/&gt;</html:br>b</rt>'
		);
	}

	public function testCanParseTagsWithSingleQuotedAttributes()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');
		$this->cb->RawHTML->allowAttribute('b', 'class');

		$this->assertParsing(
			"Hello <b class='bar' title='foo'>world</b>!",
			"<rt xmlns:html='http://www.w3.org/1999/xhtml'>Hello <html:b class='bar' title='foo'><st>&lt;b class='bar' title='foo'&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>"
		);
	}

	public function testCanParseTagsWithDoubleQuotedAttributes()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');
		$this->cb->RawHTML->allowAttribute('b', 'class');

		$this->assertParsing(
			'Hello <b class="bar" title="foo">world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b class="bar" title="foo"><st>&lt;b class="bar" title="foo"&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testCanParseTagsWithUnquotedAttributes()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');
		$this->cb->RawHTML->allowAttribute('b', 'class');

		$this->assertParsing(
			'Hello <b class=bar title=foo>world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b class="bar" title="foo"><st>&lt;b class=bar title=foo&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testCanParseTagsWithBooleanAttributesAndGivesThemTheAttributeNameAsValue()
	{
		$this->cb->RawHTML->allowElement('button');
		$this->cb->RawHTML->allowAttribute('button', 'hidden');
		$this->cb->RawHTML->allowAttribute('button', 'disabled');

		$this->assertParsing(
			'Click <button disabled hidden>Me</button>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Click <html:button disabled="disabled" hidden="hidden"><st>&lt;button disabled hidden&gt;</st>Me<et>&lt;/button&gt;</et></html:button>!</rt>'
		);
	}

	/**
	* @testdox Can parse self-closing tags with boolean attributes and no whitespace before the closing slash
	*/
	public function testCanParseSelfClosingTagsWithBooleanAttributes()
	{
		$this->cb->RawHTML->allowElement('input');
		$this->cb->RawHTML->allowAttribute('input', 'hidden');
		$this->cb->RawHTML->allowAttribute('input', 'disabled');

		$this->assertParsing(
			'Click <input disabled hidden/>',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Click <html:input disabled="disabled" hidden="hidden">&lt;input disabled hidden/&gt;</html:input></rt>'
		);
	}

	public function testCanParseWhitespaceAroundQuotedAttributes()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');
		$this->cb->RawHTML->allowAttribute('b', 'class');

		$this->assertParsing(
			'Hello <b
					class = "bar"
					title
						=
						"foo">world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b class="bar" title="foo"><st>&lt;b
					class = "bar"
					title
						=
						"foo"&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testCanParseWhitespaceWithinTags()
	{
		$this->cb->RawHTML->allowElement('b');

		$this->assertParsing(
			"Hello <b\n \t>world</b\n \t>!",
			"<rt xmlns:html='http://www.w3.org/1999/xhtml'>Hello <html:b><st>&lt;b\n \t&gt;</st>world<et>&lt;/b\n \t&gt;</et></html:b>!</rt>"
		);
	}

	/**
	* @test
	*/
	public function HTML_entities_in_attribute_values_are_decoded()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');

		$this->assertParsing(
			'Hello <b title="A&amp;B">world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b title="A&amp;B"><st>&lt;b title="A&amp;amp;B"&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testMakesUseOfCustomNamespacePrefix()
	{
		$this->cb->loadPlugin('RawHTML', null, array('namespacePrefix' => 'xxx'));
		$this->cb->RawHTML->allowElement('b');

		$this->assertParsing(
			'Hello <b>world</b>!',
			'<rt xmlns:xxx="http://www.w3.org/1999/xhtml">Hello <xxx:b><st>&lt;b&gt;</st>world<et>&lt;/b&gt;</et></xxx:b>!</rt>'
		);
	}

	public function testElementNamesAreLowercased()
	{
		$this->cb->RawHTML->allowElement('b');

		$this->assertParsing(
			'Hello <B>world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b><st>&lt;B&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testAttributesAreLowercased()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');

		$this->assertParsing(
			'Hello <b TITLE="foo">world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b title="foo"><st>&lt;b TITLE="foo"&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testBooleanAttributesAreLowercased()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'disabled');

		$this->assertParsing(
			'Hello <b DISABLED>world</b>!',
			'<rt xmlns:html="http://www.w3.org/1999/xhtml">Hello <html:b disabled="disabled"><st>&lt;b DISABLED&gt;</st>world<et>&lt;/b&gt;</et></html:b>!</rt>'
		);
	}

	public function testWholeElementNamesAreParsed()
	{
		$this->cb->RawHTML->allowElement('b');

		$this->assertParsing(
			'<boo/>',
			'<pt>&lt;boo/&gt;</pt>'
		);
	}
}