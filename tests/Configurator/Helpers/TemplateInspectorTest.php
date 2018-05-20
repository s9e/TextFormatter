<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateInspector
*/
class TemplateInspectorTest extends Test
{
	/**
	* @testdox evaluate() returns the return value of an XPath expression
	*/
	public function testEvaluate()
	{
		$templateInspector = new TemplateInspector('<br/>');

		$this->assertEquals(1, $templateInspector->evaluate('count(//br)'));
		$this->assertEquals(0, $templateInspector->evaluate('count(//b)'));
	}

	/**
	* @testdox Test cases
	* @dataProvider getTemplateInspectorTests
	*/
	public function test($title, $xslSrc, $rule, $xslTrg = null)
	{
		$src = new TemplateInspector($xslSrc);
		$trg = new TemplateInspector($xslTrg);

		$assert = ($rule[0] === '!') ? 'assertFalse' : 'assertTrue';
		$method = ltrim($rule, '!');

		$this->$assert($src->$method($trg), $title);
	}

	public function getTemplateInspectorTests()
	{
		return [
			[
				'<span> does not allow <div> as child',
				'<span><xsl:apply-templates/></span>',
				'!allowsChild',
				'<div><xsl:apply-templates/></div>'
			],
			[
				'<span> does not allow <div> as child even with a <span> sibling',
				'<span><xsl:apply-templates/></span>',
				'!allowsChild',
				'<span>xxx</span><div><xsl:apply-templates/></div>'
			],
			[
				'<span> and <div> does not allow <span> and <div> as child',
				'<span><xsl:apply-templates/></span><div><xsl:apply-templates/></div>',
				'!allowsChild',
				'<span/><div/>'
			],
			[
				'<li> closes parent <li>',
				'<li/>',
				'closesParent',
				'<li><xsl:apply-templates/></li>'
			],
			[
				'<div> closes parent <p>',
				'<div/>',
				'closesParent',
				'<p><xsl:apply-templates/></p>'
			],
			[
				'<p> closes parent <p>',
				'<p/>',
				'closesParent',
				'<p><xsl:apply-templates/></p>'
			],
			[
				'<div> does not close parent <div>',
				'<div/>',
				'!closesParent',
				'<div><xsl:apply-templates/></div>'
			],
			[
				// This test mainly exist to ensure nothing bad happens with HTML tags that don't
				// have a "cp" value in TemplateInspector::$htmlElements
				'<span> does not close parent <span>',
				'<span/>',
				'!closesParent',
				'<span><xsl:apply-templates/></span>'
			],
			[
				'<td> closes parent <td>',
				'<td><xsl:apply-templates/></td>',
				'closesParent',
				'<td><xsl:apply-templates/></td>'
			],
			[
				'<tr> closes parent <td>',
				'<tr><xsl:apply-templates/></tr>',
				'closesParent',
				'<td><xsl:apply-templates/></td>'
			],
			[
				'<tr> closes parent <th>',
				'<tr><xsl:apply-templates/></tr>',
				'closesParent',
				'<th><xsl:apply-templates/></th>'
			],
			[
				'<tbody> closes parent <th>',
				'<tbody><xsl:apply-templates/></tbody>',
				'closesParent',
				'<th><xsl:apply-templates/></th>'
			],
			[
				'<table> does not close parent <td>',
				'<table><xsl:apply-templates/></table>',
				'!closesParent',
				'<td><xsl:apply-templates/></td>'
			],
			[
				'<a> denies <a> as descendant',
				'<a><xsl:apply-templates/></a>',
				'!allowsDescendant',
				'<a/>'
			],
			[
				'<a> allows <img> with no usemap attribute as child',
				'<a><xsl:apply-templates/></a>',
				'allowsChild',
				'<img/>'
			],
			[
				'<a> denies <img usemap="#foo"> as child',
				'<a><xsl:apply-templates/></a>',
				'!allowsChild',
				'<img usemap="#foo"/>'
			],
			[
				'<a> does not allow <iframe> as child',
				'<a href=""><xsl:apply-templates/></a>',
				'!allowsChild',
				'<iframe/>'
			],
			[
				'<div><a> allows <div> as child',
				'<div><a><xsl:apply-templates/></a></div>',
				'allowsChild',
				'<div/>'
			],
			[
				'<span><a> denies <div> as child',
				'<span><a><xsl:apply-templates/></a></span>',
				'!allowsChild',
				'<div/>'
			],
			[
				'<audio> with no src attribute allows <source> as child',
				'<audio><xsl:apply-templates/></audio>',
				'allowsChild',
				'<source/>'
			],
			[
				'<audio src="..."> denies <source> as child',
				'<audio src="{@src}"><xsl:apply-templates/></audio>',
				'!allowsChild',
				'<source/>'
			],
			[
				'<a> is considered transparent',
				'<a><xsl:apply-templates/></a>',
				'isTransparent'
			],
			[
				'<a><span> is not considered transparent',
				'<a><span><xsl:apply-templates/></span></a>',
				'!isTransparent'
			],
			[
				'<span><a> is not considered transparent',
				'<span><a><xsl:apply-templates/></a></span>',
				'!isTransparent'
			],
			[
				'A template composed entirely of a single <xsl:apply-templates/> is considered transparent',
				'<xsl:apply-templates/>',
				'isTransparent'
			],
			[
				'A template with no <xsl:apply-templates/> is not considered transparent',
				'<hr/>',
				'!isTransparent'
			],
			[
				'<span> allows <unknownElement> as child',
				'<span><xsl:apply-templates/></span>',
				'allowsChild',
				'<unknownElement/>'
			],
			[
				'<unknownElement> allows <span> as child',
				'<unknownElement><xsl:apply-templates/></unknownElement>',
				'allowsChild',
				'<span/>'
			],
			[
				'<textarea> allows text nodes',
				'<textarea><xsl:apply-templates/></textarea>',
				'allowsText'
			],
			[
				'<style> allows text nodes',
				'<style><xsl:apply-templates/></style>',
				'allowsText'
			],
			[
				'<xsl:apply-templates/> allows text nodes',
				'<xsl:apply-templates/>',
				'allowsText'
			],
			[
				'<table> disallows text nodes',
				'<table><xsl:apply-templates/></table>',
				'!allowsText'
			],
			[
				'<table><tr><td> allows "Hi"',
				'<table><tr><td><xsl:apply-templates/></td></tr></table>',
				'allowsChild',
				'Hi'
			],
			[
				'<div><table> disallows "Hi"',
				'<div><table><xsl:apply-templates/></table></div>',
				'!allowsChild',
				'Hi'
			],
			[
				'<table> disallows <xsl:value-of/>',
				'<table><xsl:apply-templates/></table>',
				'!allowsChild',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<table> disallows <xsl:text>Hi</xsl:text>',
				'<table><xsl:apply-templates/></table>',
				'!allowsChild',
				'<xsl:text>Hi</xsl:text>'
			],
			[
				'<table> allows <xsl:text>  </xsl:text>',
				'<table><xsl:apply-templates/></table>',
				'allowsChild',
				'<xsl:text>  </xsl:text>'
			],
			[
				'<b> is a formatting element',
				'<b><xsl:apply-templates/></b>',
				'isFormattingElement'
			],
			[
				'<b><u> is a formatting element',
				'<b><u><xsl:apply-templates/></u></b>',
				'isFormattingElement'
			],
			[
				'<span> is not a formatting element',
				'<span><xsl:apply-templates/></span>',
				'!isFormattingElement'
			],
			[
				'<span class="..."> is a formatting element',
				'<span class="foo"><xsl:apply-templates/></span>',
				'isFormattingElement'
			],
			[
				'<span style="..."> is a formatting element',
				'<span style="color:red"><xsl:apply-templates/></span>',
				'isFormattingElement'
			],
			[
				'<span class=""> is not a formatting element',
				'<span class=""><xsl:apply-templates/></span>',
				'!isFormattingElement'
			],
			[
				'<span style=""> is not a formatting element',
				'<span style=""><xsl:apply-templates/></span>',
				'!isFormattingElement'
			],
			[
				'<span style="..." onclick="..."> is not a formatting element',
				'<span style="color:red" onclick="alert(1)"><xsl:apply-templates/></span>',
				'!isFormattingElement'
			],
			[
				'<div> is not a formatting element',
				'<div><xsl:apply-templates/></div>',
				'!isFormattingElement'
			],
			[
				'<div><u> is not a formatting element',
				'<div><u><xsl:apply-templates/></u></div>',
				'!isFormattingElement'
			],
			[
				'"Hi" is not a formatting element',
				'Hi',
				'!isFormattingElement'
			],
			[
				'A template composed entirely of a single <xsl:apply-templates/> is not a formatting element',
				'<xsl:apply-templates/>',
				'!isFormattingElement'
			],
			[
				'A template with two branches that qualify as formatting elements is a formatting element',
				'<xsl:choose><xsl:when test="@foo"><b><xsl:apply-templates/></b></xsl:when><xsl:otherwise><b><xsl:apply-templates/></b></xsl:otherwise></xsl:choose>',
				'isFormattingElement'
			],
			[
				'A template with a branch that does not qualify as a formatting element is not a formatting element',
				'<xsl:choose><xsl:when test="@foo"><b><xsl:apply-templates/></b></xsl:when><xsl:otherwise><p><xsl:apply-templates/></p></xsl:otherwise></xsl:choose>',
				'!isFormattingElement'
			],
			[
				'<img> uses the "empty" content model',
				'<img/>',
				'isEmpty'
			],
			[
				'<hr><xsl:apply-templates/></hr> uses the "empty" content model',
				'<hr><xsl:apply-templates/></hr>',
				'isEmpty'
			],
			[
				'<div><hr><xsl:apply-templates/></hr></div> uses the "empty" content model',
				'<div><hr><xsl:apply-templates/></hr></div>',
				'isEmpty'
			],
			[
				'<span> is not empty',
				'<span><xsl:apply-templates/></span>',
				'!isEmpty'
			],
			[
				'<colgroup span="2"> uses the "empty" content model',
				'<colgroup span="2"><xsl:apply-templates/></colgroup>',
				'isEmpty'
			],
			[
				'<colgroup> does not use the "empty" content model',
				'<colgroup><xsl:apply-templates/></colgroup>',
				'!isEmpty'
			],
			[
				'<span> allows elements',
				'<span><xsl:apply-templates/></span>',
				'allowsChildElements'
			],
			[
				'<span> with no <xsl:apply-templates/> does not allow elements',
				'<span/>',
				'!allowsChildElements'
			],
			[
				'<script> does not allow elements even if it has an <xsl:apply-templates/> child',
				'<script><xsl:apply-templates/></script>',
				'!allowsChildElements'
			],
			[
				'<script> does not allow <span> as a child, even if it has an <xsl:apply-templates/> child',
				'<script><xsl:apply-templates/></script>',
				'!allowsChild',
				'<span/>'
			],
			[
				'<script> does not allow <span> as a descendant, even if it has an <xsl:apply-templates/> child',
				'<script><xsl:apply-templates/></script>',
				'!allowsDescendant',
				'<span/>'
			],
			[
				'<pre> preserves new lines',
				'<pre><xsl:apply-templates/></pre>',
				'preservesNewLines'
			],
			[
				'<pre><code> preserves new lines',
				'<pre><code><xsl:apply-templates/></code></pre>',
				'preservesNewLines'
			],
			[
				'<span> does not preserve new lines',
				'<span><xsl:apply-templates/></span>',
				'!preservesNewLines'
			],
			[
				'<span style="white-space: pre"> preserves new lines',
				'<span style="white-space: pre"><xsl:apply-templates/></span>',
				'preservesNewLines'
			],
			[
				'<span><xsl:if test="@foo"><xsl:attribute name="style">white-space:pre</xsl:attribute></xsl:if> preserves new lines',
				'<span><xsl:if test="@foo"><xsl:attribute name="style">white-space:pre</xsl:attribute></xsl:if><xsl:apply-templates/></span>',
				'preservesNewLines'
			],
			[
				'<pre style="white-space: normal"> does not preserve new lines',
				'<pre style="white-space: normal"><xsl:apply-templates/></pre>',
				'!preservesNewLines'
			],
			[
				'<span style="white-space: pre-line"><span style="white-space: inherit"> preserves new lines',
				'<span style="white-space: pre-line"><span style="white-space: inherit"><xsl:apply-templates/></span></span>',
				'preservesNewLines'
			],
			[
				'<span style="white-space: pre"><span style="white-space: normal"> preserves new lines',
				'<span style="white-space: pre"><span style="white-space: normal"><xsl:apply-templates/></span></span>',
				'!preservesNewLines'
			],
			[
				'<img/> is void',
				'<img/>',
				'isVoid'
			],
			[
				'<img><xsl:apply-templates/></img> is void',
				'<img><xsl:apply-templates/></img>',
				'isVoid'
			],
			[
				'<span> is not void',
				'<span><xsl:apply-templates/></span>',
				'!isVoid'
			],
			[
				'<xsl:apply-templates/> is not void',
				'<xsl:apply-templates/>',
				'!isVoid'
			],
			[
				'<blockquote> is a block-level element',
				'<blockquote><xsl:apply-templates/></blockquote>',
				'isBlock'
			],
			[
				'<span> is not a block-level element',
				'<span><xsl:apply-templates/></span>',
				'!isBlock'
			],
			[
				'<div style="display:inline"> is not a block-level element',
				'<div style="display:inline"><xsl:apply-templates/></div>',
				'!isBlock'
			],
			[
				'<div style="display: none"> is not a block-level element',
				'<div style="display: none"><xsl:apply-templates/></div>',
				'!isBlock'
			],
			[
				'<span style="display:block"> is a block-level element',
				'<span style="display:block"><xsl:apply-templates/></span>',
				'isBlock'
			],
			[
				'<div><xsl:attribute name="style">display:inline</xsl:attribute></div> is not a block-level element',
				'<div><xsl:attribute name="style">display:inline</xsl:attribute><xsl:apply-templates/></div>',
				'!isBlock'
			],
			[
				'<br/> is not passthrough',
				'<br/>',
				'!isPassthrough'
			],
			[
				'<b/> is not passthrough',
				'<b/>',
				'!isPassthrough'
			],
			[
				'<b><xsl:apply-templates/></b> is passthrough',
				'<b><xsl:apply-templates/></b>',
				'isPassthrough'
			],
			[
				'<ruby> allows <rb> as a child',
				'<ruby><xsl:apply-templates/></ruby>',
				'allowsChild',
				'<rb/>'
			],
			[
				'<ruby> allows <rp> as a child',
				'<ruby><xsl:apply-templates/></ruby>',
				'allowsChild',
				'<rp/>'
			],
			[
				'<ruby> allows <rt> as a child',
				'<ruby><xsl:apply-templates/></ruby>',
				'allowsChild',
				'<rt/>'
			],
			[
				'<ruby> allows <rtc> as a child',
				'<ruby><xsl:apply-templates/></ruby>',
				'allowsChild',
				'<rtc/>'
			],
			[
				'<ruby> does not allow <blockquote> as a child',
				'<ruby><xsl:apply-templates/></ruby>',
				'!allowsChild',
				'<blockquote/>'
			],
			[
				'<ul> does not allow <br> as a child',
				'<ul><xsl:apply-templates/></ul>',
				'!allowsChild',
				'<br/>'
			],
			[
				'<ul> allows <br> as a descendant',
				'<ul><xsl:apply-templates/></ul>',
				'allowsDescendant',
				'<br/>'
			],
			[
				'Mixed inline/block template does not allow block templates',
				'<div><xsl:apply-templates/></div><span><xsl:apply-templates/></span>',
				'!allowsChild',
				'<div/>'
			],
			[
				'Mixed inline/block template allows inline templates',
				'<div><xsl:apply-templates/></div><span><xsl:apply-templates/></span>',
				'allowsChild',
				'<span/>'
			],
			// https://www.w3.org/TR/html52/changes.html#fixing-bugs-and-matching-reality-better
			[
				'<div> allows <style> as child',
				'<div><xsl:apply-templates/></div>',
				'allowsChild',
				'<style/>'
			],
			[
				'<p> denies <style> as child',
				'<p><xsl:apply-templates/></p>',
				'!allowsChild',
				'<style/>'
			],
			[
				'<dl> allows <div> as child',
				'<dl><xsl:apply-templates/></dl>',
				'allowsChild',
				'<div/>'
			],
			[
				'<dl> denies <p> as child',
				'<dl><xsl:apply-templates/></dl>',
				'!allowsChild',
				'<p/>'
			],
		];
	}
}