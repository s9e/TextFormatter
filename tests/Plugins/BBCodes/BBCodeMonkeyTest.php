<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use Exception;
use RuntimeException;
use s9e\TextFormatter\ConfigBuilder\Items\Filter;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;
use s9e\TextFormatter\Plugins\BBCodes\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey;
use s9e\TextFormatter\Tests\Test;

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
	* @testdox parse() works
	* @dataProvider getBBCodeTests
	*/
	public function testBBCodes($usage, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$actual = BBCodeMonkey::parse($usage);

		if (!($expected instanceof Exception))
		{
			$this->assertEquals($expected, $actual);
		}
	}

	/**
	* @testdox replaceTokens() works
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

	public function getBBCodeTests()
	{
		return array(
			array(
				'[b]{TEXT}[/B]',
				array(
					'name'   => 'B',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => array(),
					'passthroughToken' => 'TEXT'
				)
			),
			array(
				'[url={URL;useContent}]{TEXT}[/url]',
				array(
					'name'   => 'URL',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('url'),
						'defaultAttribute'  => 'url'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array('#url')
							)
						)
					)),
					'tokens' => array(
						'URL'     => 'url'
					),
					'passthroughToken' => 'TEXT'
				)
			),
			array(
				'[flash={NUMBER1},{NUMBER2}]{URL}[/flash]',
				array(
					'name'   => 'FLASH',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('content'),
						'defaultAttribute'  => 'flash'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							'flash' => array('/^(?<flash0>\\d+),(?<flash1>\\d+)$/D')
						),
						'attributes' => array(
							'content' => array(
								'filterChain' => array('#url')
							),
							'flash0' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^(?:\\d+)$/D'))
								)
							),
							'flash1' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^(?:\\d+)$/D'))
								)
							)
						)
					)),
					'tokens' => array(
						'NUMBER1' => 'flash0',
						'NUMBER2' => 'flash1',
						'URL' => 'content'
					),
					'passthroughToken' => null
				)
			)
		);
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