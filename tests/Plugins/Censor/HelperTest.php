<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Plugins\Censor\Helper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Helper
*/
class HelperTest extends Test
{
	/**
	* @testdox censorHtml() censors text nodes in HTML
	*/
	public function testCensorHtml()
	{
		$this->configurator->Censor->add('foo');

		$this->assertSame(
			'**** bar baz <b>****</b>',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz <b>foo</b>')
		);
	}

	/**
	* @testdox censorHtml() uses custom replacements
	*/
	public function testCensorHtmlCustom()
	{
		$this->configurator->Censor->add('foo', 'bar');

		$this->assertSame(
			'bar bar baz <b>bar</b>',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz <b>foo</b>')
		);
	}

	/**
	* @testdox censorHtml() escapes custom replacements
	*/
	public function testCensorHtmlEscape()
	{
		$this->configurator->Censor->add('bar', '<...>') ;

		$this->assertSame(
			'foo &lt;...&gt; baz',
			$this->configurator->Censor->getHelper()->censorHtml('foo bar baz')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML tag names
	*/
	public function testCensorHtmlTagNames()
	{
		$this->configurator->Censor->add('span') ;

		$this->assertSame(
			'<span>****</span>',
			$this->configurator->Censor->getHelper()->censorHtml('<span>span</span>')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML tags
	*/
	public function testCensorHtmlTags()
	{
		$this->configurator->Censor->add('<br>') ;

		$this->assertSame(
			'<br>',
			$this->configurator->Censor->getHelper()->censorHtml('<br>')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML attribute names
	*/
	public function testCensorHtmlAttributeNames()
	{
		$this->configurator->Censor->add('title') ;

		$this->assertSame(
			'<span title="">****</span>',
			$this->configurator->Censor->getHelper()->censorHtml('<span title="">title</span>')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML attribute values by default
	*/
	public function testCensorHtmlAttributeValuesDefault()
	{
		$this->configurator->Censor->add('title') ;
		$html = '<span title="title">title</span>';

		$this->assertSame(
			'<span title="title">****</span>',
			$this->configurator->Censor->getHelper()->censorHtml($html)
		);
	}

	/**
	* @testdox censorHtml() replaces HTML attribute values if its second argument is true
	*/
	public function testCensorHtmlAttributeValuesTrue()
	{
		$this->configurator->Censor->add('title') ;
		$html = '<span title="title">title</span>';

		$this->assertSame(
			'<span title="****">****</span>',
			$this->configurator->Censor->getHelper()->censorHtml($html, true)
		);
	}

	/**
	* @testdox censorHtml() replaces text in quotes in text nodes and in attribute values
	*/
	public function testCensorHtmlTextInQuotesAndAttributes()
	{
		$this->configurator->Censor->add('title') ;
		$html = '<span title="title">title "title"</span>';

		$this->assertSame(
			'<span title="****">**** "****"</span>',
			$this->configurator->Censor->getHelper()->censorHtml($html, true)
		);
	}

	/**
	* @testdox censorHtml() replaces text in quotes in text nodes without replacing attribute values
	*/
	public function testCensorHtmlTextInQuotesNoAttributes()
	{
		$this->configurator->Censor->add('title') ;
		$html = '<span title="title">title "title"</span>';

		$this->assertSame(
			'<span title="title">**** "****"</span>',
			$this->configurator->Censor->getHelper()->censorHtml($html, false)
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML entities
	*/
	public function testCensorHtmlEntities()
	{
		$this->configurator->Censor->add('*m*', 'xxx') ;

		$this->assertSame(
			' xxx &amp; xxx ',
			$this->configurator->Censor->getHelper()->censorHtml(' imp &amp; amp ')
		);
	}

	/**
	* @testdox censorHtml() does not replace HTML numeric entities
	*/
	public function testCensorHtmlNumericEntities()
	{
		$this->configurator->Censor->add('*x*', 'xxx') ;

		$this->assertSame(
			' xxx &#x11; xxx ',
			$this->configurator->Censor->getHelper()->censorHtml(' x11 &#x11; x11 ')
		);
	}

	/**
	* @testdox censorHtml() censors special characters
	*/
	public function testCensorHtmlSpecialChars()
	{
		$this->configurator->Censor->add('<br>') ;

		$this->assertSame(
			'<br> ****',
			$this->configurator->Censor->getHelper()->censorHtml('<br> &lt;br&gt;')
		);
	}

	/**
	* @testdox censorHtml() censors special characters with the correct replacement
	*/
	public function testCensorHtmlSpecialCharsReplacement()
	{
		$this->configurator->Censor->add('<br>', '??') ;

		$this->assertSame(
			'<br> ??',
			$this->configurator->Censor->getHelper()->censorHtml('<br> &lt;br&gt;')
		);
	}

	/**
	* @testdox censorHtml() ignores words on the allowed list
	*/
	public function testCensorHtmlAllowed()
	{
		$this->configurator->Censor->add('foo*') ;
		$this->configurator->Censor->allow('fool') ;

		$this->assertSame(
			'Dat fool went ****',
			$this->configurator->Censor->getHelper()->censorHtml('Dat fool went foobar')
		);
	}

	/**
	* @testdox censorText() censors plain text
	*/
	public function testCensorText()
	{
		$this->configurator->Censor->add('foo') ;

		$this->assertSame(
			'**** bar baz',
			$this->configurator->Censor->getHelper()->censorText('foo bar baz')
		);
	}

	/**
	* @testdox censorText() uses custom replacements
	*/
	public function testCensorTextCustom()
	{
		$this->configurator->Censor->add('foo', 'bar');

		$this->assertSame(
			'bar bar baz',
			$this->configurator->Censor->getHelper()->censorText('foo bar baz')
		);
	}

	/**
	* @testdox censorText() ignores words on the allowed list
	*/
	public function testCensorTextAllowed()
	{
		$this->configurator->Censor->add('foo*') ;
		$this->configurator->Censor->allow('fool') ;

		$this->assertSame(
			'Dat fool went ****',
			$this->configurator->Censor->getHelper()->censorText('Dat fool went foobar')
		);
	}

	/**
	* @testdox isCensored('word') returns TRUE if '*or*' is censored
	*/
	public function testIsCensoredTrue()
	{
		$this->configurator->Censor->add('*or*') ;
		$this->assertTrue($this->configurator->Censor->getHelper()->isCensored('word'));
	}

	/**
	* @testdox isCensored('word') returns FALSE if '*xx*' is censored
	*/
	public function testIsCensoredFalse()
	{
		$this->configurator->Censor->add('*xx*') ;
		$this->assertFalse($this->configurator->Censor->getHelper()->isCensored('word'));
	}

	/**
	* @testdox isCensored('word') returns FALSE if '*or*' is censored and 'word' is allowed
	*/
	public function testIsCensoredAllowed()
	{
		$this->configurator->Censor->add('*or*') ;
		$this->configurator->Censor->allow('word') ;
		$this->assertFalse($this->configurator->Censor->getHelper()->isCensored('word'));
	}
}