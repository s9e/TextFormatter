<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use DOMXPath;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\NodeLocator
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateHelper
*/
class NodeLocatorTest extends Test
{
	public function runTestGetNodes($methodName, $args, $template, $query)
	{
		$dom = new DOMDocument;
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';
		$dom->loadXML($xsl);

		if ($query)
		{
			$xpath    = new DOMXPath($dom);
			$expected = iterator_to_array($xpath->query($query), false);
		}
		else
		{
			$expected = [];
		}

		array_unshift($args, $dom);
		$actual = call_user_func_array('s9e\\TextFormatter\\Configurator\\Helpers\\TemplateHelper::' . $methodName, $args);

		$this->assertEquals(count($expected), count($actual), 'Wrong node count');

		$i   = -1;
		$cnt = count($expected);
		while (++$i < $cnt)
		{
			$this->assertTrue(
				$expected[$i]->isSameNode($actual[$i]),
				'Node ' . $i . ' does not match'
			);
		}
	}

	/**
	* @testdox getObjectParamsByRegexp() tests
	* @dataProvider getObjectParamsByRegexpTests
	*/
	public function testGetObjectParamsByRegexp($regexp, $template, $query = null)
	{
		$this->runTestGetNodes('getObjectParamsByRegexp', [$regexp], $template, $query);
	}

	/**
	* @testdox getCSSNodes() tests
	* @dataProvider getCSSNodesTests
	*/
	public function testGetCSSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getCSSNodes', [], $template, $query);
	}

	/**
	* @testdox getJSNodes() tests
	* @dataProvider getJSNodesTests
	*/
	public function testGetJSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getJSNodes', [], $template, $query);
	}

	/**
	* @testdox getURLNodes() tests
	* @dataProvider getURLNodesTests
	*/
	public function testGetURLNodes($template, $query = null)
	{
		$this->runTestGetNodes('getURLNodes', [], $template, $query);
	}

	public function getObjectParamsByRegexpTests()
	{
		return [
			[
				'//',
				'...',
				null
			],
			[
				'/^allowscriptaccess$/i',
				'<embed AllowScriptAccess="always"/>',
				'//@*'
			],
			[
				'/^allowscriptaccess$/i',
				'<div allowscriptaccess="always"/>',
				null
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:attribute name="AllowScriptAccess"/></embed>',
				'//xsl:attribute'
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:if test="@foo"><xsl:attribute name="AllowScriptAccess"/></xsl:if></embed>',
				'//xsl:attribute'
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:copy-of select="@allowscriptaccess"/></embed>',
				'//xsl:copy-of'
			],
			[
				'/^allowscriptaccess$/i',
				'<object><param name="AllowScriptAccess"/><param name="foo"/></object>',
				'//param[@name != "foo"]'
			],
			[
				'/^allowscriptaccess$/i',
				'<object><xsl:if test="@foo"><param name="AllowScriptAccess"/><param name="foo"/></xsl:if></object>',
				'//param[@name != "foo"]'
			],
		];
	}

	public function getCSSNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<b style="1">...<i style="2">...</i></b><b style="3">...</b>',
				'//@style'
			],
			[
				'<b STYLE="">...</b>',
				'//@*'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="style"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="STYLE"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:copy-of select="@style"/></b>',
				'//xsl:copy-of'
			],
			[
				'<style/>',
				'*'
			],
			[
				'<STYLE/>',
				'*'
			],
			[
				'<xsl:element name="style"/>',
				'*'
			],
			[
				'<xsl:element name="STYLE"/>',
				'*'
			],
		];
	}

	public function getJSNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<script/>',
				'*'
			],
			[
				'<SCRIPT/>',
				'*'
			],
			[
				'<xsl:element name="script"/>',
				'*'
			],
			[
				'<xsl:element name="SCRIPT"/>',
				'*'
			],
			[
				'<b onclick=""/><i title=""/><b onfocus=""/>',
				'//@onclick | //@onfocus'
			],
			[
				'<b ONHOVER=""/>',
				'//@*'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="onclick"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="ONCLICK"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:copy-of select="@onclick"/></b>',
				'//xsl:copy-of'
			],
			[
				'<b data-s9e-livepreview-postprocess=""/>',
				'//@*'
			],
		];
	}

	public function getURLNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<form action=""/>',
				'//@action'
			],
			[
				'<body background=""/>',
				'//@background'
			],
			[
				'<blockquote cite=""/>',
				'//@cite',
			],
			[
				'<cite/>',
				null
			],
			[
				'<object classid=""/>',
				'//@classid'
			],
			[
				'<object codebase=""/>',
				'//@codebase'
			],
			[
				'<object data=""/>',
				'//@data'
			],
			[
				'<input formaction=""/>',
				'//@formaction'
			],
			[
				'<a href=""/>',
				'//@href'
			],
			[
				'<command icon=""/>',
				'//@icon'
			],
			[
				'<img longdesc=""/>',
				'//@longdesc'
			],
			[
				'<cache manifest=""/>',
				'//@manifest'
			],
			[
				'<head profile=""/>',
				'//@profile'
			],
			[
				'<video poster=""/>',
				'//@poster'
			],
			[
				'<img src=""/>',
				'//@src'
			],
			[
				'<img lowsrc=""/>',
				'//@lowsrc'
			],
			[
				'<img dynsrc=""/>',
				'//@dynsrc'
			],
			[
				'<input usemap=""/>',
				'//@usemap'
			],
			[
				'<object><param name="movie" value=""/></object>',
				'//@value'
			],
			[
				'<OBJECT><PARAM NAME="MOVIE" VALUE=""/></OBJECT>',
				'//@value'
			],
			[
				'<object><param name="dataurl" value=""/></object>',
				'//@value'
			],
		];
	}

	/**
	* @testdox getElementsByRegexp() can return elements created via <xsl:copy-of/>
	*/
	public function testGetElementsByRegexp()
	{
		$dom = TemplateLoader::load('<xsl:copy-of select="x"/><xsl:copy-of select="foo"/>');

		$this->assertSame(
			[$dom->firstChild->firstChild->nextSibling],
			TemplateHelper::getElementsByRegexp($dom, '/^foo$/')
		);
	}
}