<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ElementInspector
*/
class ElementInspectorTest extends Test
{
	/**
	* @testdox Test cases
	* @dataProvider getElementInspectorTests
	*/
	public function test($methodName, $args, $expected)
	{
		$args = (array) $args;
		foreach ($args as $k => $v)
		{
			$args[$k] = TemplateHelper::loadTemplate($v)->documentElement->firstChild;
		}

		$actual = call_user_func_array('s9e\\TextFormatter\\Configurator\\Helpers\\ElementInspector::' . $methodName, $args);
		$this->assertEquals($expected, $actual);
	}

	public function getElementInspectorTests()
	{
		return [
			[
				'closesParent',
				['<li><xsl:apply-templates/></li>', '<li><xsl:apply-templates/></li>'],
				true
			],
			[
				'closesParent',
				['<ul><xsl:apply-templates/></ul>', '<li><xsl:apply-templates/></li>'],
				false
			],
			[
				'closesParent',
				['<li><xsl:apply-templates/></li>', '<ul><xsl:apply-templates/></ul>'],
				false
			],
			[
				'closesParent',
				['<p><xsl:apply-templates/></p>', '<p><xsl:apply-templates/></p>'],
				true
			],
			[
				'closesParent',
				['<div><xsl:apply-templates/></div>', '<p><xsl:apply-templates/></p>'],
				true
			],
			[
				'closesParent',
				['<p><xsl:apply-templates/></p>', '<div><xsl:apply-templates/></div>'],
				false
			],
			[
				'disallowsText',
				'<ul><xsl:apply-templates/></ul>',
				true
			],
			[
				'disallowsText',
				'<div><xsl:apply-templates/></div>',
				false
			],
			[
				'getAllowChildBitfield',
				'<time datetime="2001-05-15T19:00">May 15</time>',
				"\4"
			],
			[
				'getAllowChildBitfield',
				'<time>May 15</time>',
				"\0"
			],
			[
				'getCategoryBitfield',
				'<ul><li><xsl:apply-templates/></li></ul>',
				"\3"
			],
			[
				'getCategoryBitfield',
				'<ul><xsl:apply-templates/></ul>',
				"\1"
			],
			[
				'getDenyDescendantBitfield',
				'<video src="{src}"><xsl:apply-templates/></video>',
				"\0\0\0\0\0\2"
			],
			[
				'getDenyDescendantBitfield',
				'<video><xsl:apply-templates/></video>',
				"\0\0\0\0\0\0"
			],
			[
				'isBlock',
				'<div><xsl:apply-templates/></div>',
				true
			],
			[
				'isBlock',
				'<span><xsl:apply-templates/></span>',
				false
			],
			[
				'isEmpty',
				'<hr/>',
				true
			],
			[
				'isEmpty',
				'<colgroup span="2"><xsl:apply-templates/></colgroup>',
				true
			],
			[
				'isEmpty',
				'<colgroup><xsl:apply-templates/></colgroup>',
				false
			],
			[
				'isFormattingElement',
				'<b><xsl:apply-templates/></b>',
				true
			],
			[
				'isFormattingElement',
				'<span><xsl:apply-templates/></span>',
				false
			],
			[
				'isTextOnly',
				'<script><xsl:apply-templates/></script>',
				true
			],
			[
				'isTextOnly',
				'<span><xsl:apply-templates/></span>',
				false
			],
			[
				'isTransparent',
				'<a><xsl:apply-templates/></a>',
				true
			],
			[
				'isTransparent',
				'<span><xsl:apply-templates/></span>',
				false
			],
			[
				'isVoid',
				'<hr/>',
				true
			],
			[
				'isVoid',
				'<span><xsl:apply-templates/></span>',
				false
			],
			[
				'preservesWhitespace',
				'<pre><xsl:apply-templates/></pre>',
				true
			],
			[
				'preservesWhitespace',
				'<span><xsl:apply-templates/></span>',
				false
			],
		];
	}
}