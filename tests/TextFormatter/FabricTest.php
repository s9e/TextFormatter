<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

class FabricTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Fabric');
	}

	public function testLink()
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
	* @depends testLink
	*/
	public function testLinkWithTitle()
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

	public function testLinkWithNoUrl()
	{
		$this->assertTransformation(
			'"link text(with title)"',
			'<pt>"link text(with title)"</pt>',
			'"link text(with title)"'
		);
	}

	public function testImage()
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
	* @depends testImage
	*/
	public function testImageWithAltText()
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
	* @depends testImage
	*/
	public function testImageWithLink()
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
	* @depends testImageWithAltText
	* @depends testImageWithLink
	*/
	public function testImageWithAltTextAndLink()
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
		public function testPhraseModifierEmphasis()
	{
		$this->assertTransformation(
			'_emphasis_',
			'<rt>
				<EM><st>_</st>emphasis<et>_</et></EM>
			</rt>',
			'<em>emphasis</em>'
		);
	}

	public function testPhraseModifierStrong()
	{
		$this->assertTransformation(
			'*strong*',
			'<rt>
				<STRONG><st>*</st>strong<et>*</et></STRONG>
			</rt>',
			'<strong>strong</strong>'
		);
	}

	public function testPhraseModifierItalic()
	{
		$this->assertTransformation(
			'__italic__',
			'<rt>
				<I><st>__</st>italic<et>__</et></I>
			</rt>',
			'<i>italic</i>'
		);
	}

	public function testPhraseModifierBold()
	{
		$this->assertTransformation(
			'**bold**',
			'<rt>
				<B><st>**</st>bold<et>**</et></B>
			</rt>',
			'<b>bold</b>'
		);
	}

	public function testPhraseModifierCitation()
	{
		$this->assertTransformation(
			'??citation??',
			'<rt>
				<CITE><st>??</st>citation<et>??</et></CITE>
			</rt>',
			'<cite>citation</cite>'
		);
	}

	public function testPhraseModifierDeletedText()
	{
		$this->assertTransformation(
			'-deleted text-',
			'<rt>
				<DEL><st>-</st>deleted text<et>-</et></DEL>
			</rt>',
			'<del>deleted text</del>'
		);
	}

	public function testPhraseModifierInsertedText()
	{
		$this->assertTransformation(
			'+inserted text+',
			'<rt>
				<INS><st>+</st>inserted text<et>+</et></INS>
			</rt>',
			'<ins>inserted text</ins>'
		);
	}

	public function testPhraseModifierSuperscript()
	{
		$this->assertTransformation(
			'^superscript^',
			'<rt>
				<SUPER><st>^</st>superscript<et>^</et></SUPER>
			</rt>',
			'<sup>superscript</sup>'
		);
	}

	public function testPhraseModifierSubscript()
	{
		$this->assertTransformation(
			'~subscript~',
			'<rt>
				<SUB><st>~</st>subscript<et>~</et></SUB>
			</rt>',
			'<sub>subscript</sub>'
		);
	}

	public function testPhraseModifierSpan()
	{
		$this->assertTransformation(
			'%span%',
			'<rt>
				<SPAN><st>%</st>span<et>%</et></SPAN>
			</rt>',
			'<span>span</span>'
		);
	}

	public function testPhraseModifierSpanWithClass()
	{
		$this->assertTransformation(
			'%(foo)span%',
			'<rt>
				<SPAN class="foo"><st>%(foo)</st>span<et>%</et></SPAN>
			</rt>',
			'<span class="foo">span</span>'
		);
	}

	public function testPhraseModifierSpanWithMultipleClasses()
	{
		$this->assertTransformation(
			'%(foo bar)span%',
			'<rt>
				<SPAN class="foo bar"><st>%(foo bar)</st>span<et>%</et></SPAN>
			</rt>',
			'<span class="foo bar">span</span>'
		);
	}

	public function testPhraseModifierCode()
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
	public function testPhraseModifierCodeWithStx()
	{
		$this->assertTransformation(
			'@(php)code@',
			'<rt>
				<CODE stx="php"><st>@(php)</st>code<et>@</et></CODE>
			</rt>',
			'<pre class="brush:php">code</pre>'
		);
	}

}