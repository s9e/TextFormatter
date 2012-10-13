<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey
*/
class BBCodeMonkeyTest extends Test
{
	/*
	* @testdox Attributes from attribute preprocessors are automatically created using their subpattern as filtering regexp
	*/
	public function _testAttributesFromAttributePreprocessors()
	{
//		echo BBCodeMonkey::replaceTokens('<b>{NUMBER1}</b>', array('NUMBER1'=>'width'), null);
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2}]{URL}[/flash]');
//		BBCodeMonkey::parse('[flash={PARSE=/^(?<width>\\d+),(?<height>\\d+)$/}]{URL}[/flash]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} flash={NUMBER2}-{NUMBER1}]{URL}[/flash]');
//		BBCodeMonkey::parse('[youtube]{PARSE=#http://foo?id=(?<id>\\w+)#}[/youtube]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} foo={INT}]{URL}[/flash]');
	}

	/**
	* @dataProvider getTemplateTests
	*/
	public function testTemplates($template, $tokens, $passthroughToken, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$actual = BBCodeMonkey::replaceTokens($template, $tokens, $passthroughToken);

		if (!($expected instanceof Exception))
		{
			$this->assertSame($expected, $actual);
		}
	}

	public function getTemplateTests()
	{
		return array(
			array(
				'<b>{TEXT}</b>',
				array(),
				'TEXT',
				'<b><xsl:apply-templates/></b>'
			),
			array(
				'<b>{TEXT}</b>',
				array(),
				null,
				new RuntimeException('Token {TEXT} is ambiguous or undefined')
			),
			array(
				'<span title="{TEXT}"/>',
				array(),
				null,
				new RuntimeException('Token {TEXT} is ambiguous or undefined')
			),
			array(
				'<a href="{URL}">{TEXT}</a>',
				array('URL' => 'url'),
				'TEXT',
				'<a href="{@url}"><xsl:apply-templates/></a>'
			),
			array(
				'<b title="{TEXT}">{TEXT}</b>',
				array(),
				'TEXT',
				'<b title="{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}"><xsl:apply-templates/></b>'
			),
			array(
				'<span title="{ID}{ID}"/>',
				array('ID' => 'id'),
				'TEXT',
				'<span title="{@id}{@id}"/>'
			),
			array(
				'foo',
				array(),
				'TEXT',
				'foo'
			),
			array(
				'foo{TEXT}bar',
				array(),
				'TEXT',
				'foo<xsl:apply-templates/>bar'
			),
			array(
				'foo{TEXT}bar',
				array(),
				'TEXT',
				'foo<xsl:apply-templates/>bar'
			),
			array(
				'<hr><img src={IMG}><br>',
				array('IMG' => 'url'),
				'TEXT',
				'<hr/><img src="{@url}"/><br/>',
			),
			array(
				'</html><inv<alid',
				array(),
				'null',
				new RuntimeException('Invalid template')
			),
		);
	}
}