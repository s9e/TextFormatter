<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

class FabricTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Fabric');
	}

	public function testSupportsLink()
	{
		$this->assertTransformation(
			'"link text":http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>"</st>link text<et>":http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com">link text</a>'
		);
	}

	/**
	* @depends testSupportsLink
	*/
	public function testSupportsLinkWithTitle()
	{
		$this->assertTransformation(
			'"link text(with title)":http://www.example.com',
			'<rt>
				<URL title="with title" url="http://www.example.com">
					<st>"</st>link text<et>(with title)":http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com" title="with title">link text</a>'
		);
	}

	public function testSupportsLinkWithNoUrl()
	{
		$this->assertTransformation(
			'"link text(with title)"',
			'<pt>"link text(with title)"</pt>',
			'"link text(with title)"'
		);
	}

	public function testSupportsImage()
	{
		$this->assertTransformation(
			'!http://example.com/img.png!',
			'<rt>
				<IMG src="http://example.com/img.png">!http://example.com/img.png!</IMG>
			</rt>',
			'<img src="http://example.com/img.png">'
		);
	}

	/**
	* @depends testSupportsImage
	*/
	public function testSupportsImageWithAltText()
	{
		$this->assertTransformation(
			'!http://example.com/img.png(alt text)!',
			'<rt>
				<IMG alt="alt text" src="http://example.com/img.png" title="alt text">!http://example.com/img.png(alt text)!</IMG>
			</rt>',
			'<img src="http://example.com/img.png" alt="alt text" title="alt text">'
		);
	}

	/**
	* @depends testSupportsImage
	*/
	public function testSupportsImageWithLink()
	{
		$this->assertTransformation(
			'!http://example.com/img.png!:http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>!</st>
					<IMG src="http://example.com/img.png">http://example.com/img.png!</IMG>
					<et>:http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com"><img src="http://example.com/img.png"></a>'
		);
	}

	/**
	* @depends testSupportsImageWithAltText
	* @depends testSupportsImageWithLink
	*/
	public function testSupportsImageWithAltTextAndLink()
	{
		$this->assertTransformation(
			'!http://example.com/img.png(alt text)!:http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>!</st>
					<IMG alt="alt text" src="http://example.com/img.png" title="alt text">http://example.com/img.png(alt text)!</IMG>
					<et>:http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com"><img src="http://example.com/img.png" alt="alt text" title="alt text"></a>'
		);
	}
		public function testSupportsPhraseModifierEmphasis()
	{
		$this->assertTransformation(
			'_emphasis_',
			'<rt>
				<EM><st>_</st>emphasis<et>_</et></EM>
			</rt>',
			'<em>emphasis</em>'
		);
	}

	public function testSupportsPhraseModifierStrong()
	{
		$this->assertTransformation(
			'*strong*',
			'<rt>
				<STRONG><st>*</st>strong<et>*</et></STRONG>
			</rt>',
			'<strong>strong</strong>'
		);
	}

	public function testSupportsPhraseModifierItalic()
	{
		$this->assertTransformation(
			'__italic__',
			'<rt>
				<I><st>__</st>italic<et>__</et></I>
			</rt>',
			'<i>italic</i>'
		);
	}

	public function testSupportsPhraseModifierBold()
	{
		$this->assertTransformation(
			'**bold**',
			'<rt>
				<B><st>**</st>bold<et>**</et></B>
			</rt>',
			'<b>bold</b>'
		);
	}

	public function testSupportsPhraseModifierCitation()
	{
		$this->assertTransformation(
			'??citation??',
			'<rt>
				<CITE><st>??</st>citation<et>??</et></CITE>
			</rt>',
			'<cite>citation</cite>'
		);
	}

	public function testSupportsPhraseModifierDeletedText()
	{
		$this->assertTransformation(
			'-deleted text-',
			'<rt>
				<DEL><st>-</st>deleted text<et>-</et></DEL>
			</rt>',
			'<del>deleted text</del>'
		);
	}

	public function testSupportsPhraseModifierInsertedText()
	{
		$this->assertTransformation(
			'+inserted text+',
			'<rt>
				<INS><st>+</st>inserted text<et>+</et></INS>
			</rt>',
			'<ins>inserted text</ins>'
		);
	}

	public function testSupportsPhraseModifierSuperscript()
	{
		$this->assertTransformation(
			'^superscript^',
			'<rt>
				<SUPER><st>^</st>superscript<et>^</et></SUPER>
			</rt>',
			'<sup>superscript</sup>'
		);
	}

	public function testSupportsPhraseModifierSubscript()
	{
		$this->assertTransformation(
			'~subscript~',
			'<rt>
				<SUB><st>~</st>subscript<et>~</et></SUB>
			</rt>',
			'<sub>subscript</sub>'
		);
	}

	public function testSupportsPhraseModifierSpan()
	{
		$this->assertTransformation(
			'%span%',
			'<rt>
				<SPAN><st>%</st>span<et>%</et></SPAN>
			</rt>',
			'<span>span</span>'
		);
	}

	public function testSupportsPhraseModifierSpanWithClass()
	{
		$this->assertTransformation(
			'%(foo)span%',
			'<rt>
				<SPAN class="foo"><st>%(foo)</st>span<et>%</et></SPAN>
			</rt>',
			'<span class="foo">span</span>'
		);
	}

	public function testSupportsPhraseModifierSpanWithMultipleClasses()
	{
		$this->assertTransformation(
			'%(foo bar)span%',
			'<rt>
				<SPAN class="foo bar"><st>%(foo bar)</st>span<et>%</et></SPAN>
			</rt>',
			'<span class="foo bar">span</span>'
		);
	}

	public function testSupportsPhraseModifierCode()
	{
		$this->assertTransformation(
			'@code@',
			'<rt>
				<CODE stx="plain"><st>@</st>code<et>@</et></CODE>
			</rt>',
			'<pre class="brush:plain">code</pre>'
		);
	}

	/**
	* This is an extension to the Textile syntax
	*/
	public function testSupportsPhraseModifierCodeWithStx()
	{
		$this->assertTransformation(
			'@(php)code@',
			'<rt>
				<CODE stx="php"><st>@(php)</st>code<et>@</et></CODE>
			</rt>',
			'<pre class="brush:php">code</pre>'
		);
	}

	public function testSupportsAcronyms()
	{
		$this->assertTransformation(
			'ABC(Always Be Closing)',
			'<rt>
				<ACRONYM title="Always Be Closing">ABC<et>(Always Be Closing)</et></ACRONYM>
			</rt>',
			'<acronym title="Always Be Closing">ABC</acronym>'
		);
	}
}