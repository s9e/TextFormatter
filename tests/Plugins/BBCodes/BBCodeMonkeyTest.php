<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use Exception;
use InvalidArgumentException;
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
				'*invalid*',
				new InvalidArgumentException('Cannot interpret the BBCode definition')
			),
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
				'[b title={TEXT1}]{TEXT2}[/B]',
				array(
					'name'   => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array()
						)
					)),
					'tokens' => array(
						'TEXT1' => 'title'
					),
					'passthroughToken' => 'TEXT2'
				)
			),
			array(
				'[b title={TEXT1;optional;required;optional}]{TEXT2}[/B]',
				array(
					'name'   => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array()
						)
					)),
					'tokens' => array(
						'TEXT1' => 'title'
					),
					'passthroughToken' => 'TEXT2'
				)
			),
			array(
				'[b title={TEXT1;defaultValue=Title;optional}]{TEXT2}[/B]',
				array(
					'name'   => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array(
								'defaultValue' => 'Title',
								'required'     => false
							)
						)
					)),
					'tokens' => array(
						'TEXT1' => 'title'
					),
					'passthroughToken' => 'TEXT2'
				)
			),
			array(
				'[hr]',
				array(
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => array(),
					'passthroughToken' => null
				)
			),
			array(
				'[hr][/hr]',
				array(
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => array(),
					'passthroughToken' => null
				)
			),
			array(
				'[hr/]',
				array(
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => array(),
					'passthroughToken' => null
				)
			),
			array(
				'[IMG src={URL;useContent}]',
				array(
					'name'   => 'IMG',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('src'),
						'defaultAttribute'  => 'src'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'src' => array(
								'filterChain' => array('#url')
							)
						)
					)),
					'tokens' => array('URL' => 'src'),
					'passthroughToken' => null
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
				'[foo={REGEXP=/^foo$/}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^foo$/'))
								)
							)
						)
					)),
					'tokens' => array(
						'REGEXP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={REGEXP=#^foo$#}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '#^foo$#'))
								)
							)
						)
					)),
					'tokens' => array(
						'REGEXP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={REGEXP=/[a-z]{3}\\//}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/[a-z]{3}\\//'))
								)
							)
						)
					)),
					'tokens' => array(
						'REGEXP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={RANGE=2,5}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#range', array('min' => 2, 'max' => 5))
								)
							)
						)
					)),
					'tokens' => array(
						'RANGE' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={CHOICE=one,two}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^(?:one|two)$/Di'))
								)
							)
						)
					)),
					'tokens' => array(
						'CHOICE' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={CHOICE=pokémon,yugioh}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^(?:pokémon|yugioh)$/Diu'))
								)
							)
						)
					)),
					'tokens' => array(
						'CHOICE' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={CHOICE=Pokémon,YuGiOh;caseSensitive}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Filter('#regexp', array('regexp' => '/^(?:Pokémon|YuGiOh)$/Du'))
								)
							)
						)
					)),
					'tokens' => array(
						'CHOICE' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				/**
				* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=2127991
				*/
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
			),
			array(
				/**
				* @link https://www.vbulletin.com/forum/misc.php?do=bbcode#quote
				*/
				'[quote={PARSE=/(?<author>.+?)(?:;(?<id>\\d+))?/} author={TEXT1;optional} id={UINT;optional}]{TEXT2}[/quote]',
				array(
					'name'   => 'QUOTE',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'quote'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							'quote' => array('/(?<author>.+?)(?:;(?<id>\\d+))?/')
						),
						'attributes' => array(
							'author' => array(
								'required' => false
							),
							'id'     => array(
								'filterChain' => array('#uint'),
								'required' => false
							)
						)
					)),
					'tokens' => array(
						'TEXT1' => 'author',
						'UINT'  => 'id'
					),
					'passthroughToken' => 'TEXT2'
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