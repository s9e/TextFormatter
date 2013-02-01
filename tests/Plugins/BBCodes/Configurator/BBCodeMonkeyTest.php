<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Choice;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Identifier;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Int;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Map;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Number;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Range;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Simpletext;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Uint;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey
*/
class BBCodeMonkeyTest extends Test
{
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

		$bbcodeMonkey = new BBCodeMonkey(new Configurator);
		$actual = $bbcodeMonkey->parse($usage);

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

		$bm     = new BBCodeMonkey(new Configurator);
		$actual = $bm->replaceTokens($template, $tokens, $passthroughToken);

		if (!($expected instanceof Exception))
		{
			$this->assertSame($expected, $actual);
		}
	}

	public function getBBCodeTests()
	{
		return [
			[
				'*invalid*',
				new InvalidArgumentException('Cannot interpret the BBCode definition')
			],
			[
				'[föö]',
				new InvalidArgumentException("Invalid BBCode name 'föö'")
			],
			[
				'[foo bar=TEXT]{TEXT}[/foo]',
				new RuntimeException("No tokens found in bar's definition")
			],
			[
				'[foo bar={TEXT} bar={INT}]{TEXT}[/foo]',
				new RuntimeException("Attribute 'bar' is declared twice")
			],
			[
				'[foo bar={TEXT} baz={TEXT}]{TEXT}[/foo]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'bar'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'bar' => [],
							'baz' => []
						]
					]),
					'tokens' => [],
					'passthroughToken' => null
				]
			],
			[
				'[URL={URL}]{TEXT}[/URL]',
				[
					'name'   => 'URL',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'url'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new Url]
							]
						]
					]),
					'tokens' => [
						'URL' => 'url'
					],
					'passthroughToken' => 'TEXT'
				]
			],
			[
				'[b]{TEXT}[/B]',
				[
					'name'   => 'B',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => 'TEXT'
				]
			],
			[
				'[b title={TEXT1}]{TEXT2}[/B]',
				[
					'name'   => 'B',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'title'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'title' => []
						]
					]),
					'tokens' => [
						'TEXT1' => 'title'
					],
					'passthroughToken' => 'TEXT2'
				]
			],
			[
				'[b title={TEXT1;optional;required;optional}]{TEXT2}[/B]',
				[
					'name'   => 'B',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'title'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'title' => []
						]
					]),
					'tokens' => [
						'TEXT1' => 'title'
					],
					'passthroughToken' => 'TEXT2'
				]
			],
			[
				'[b title={TEXT1;defaultValue=Title;optional}]{TEXT2}[/B]',
				[
					'name'   => 'B',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'title'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'title' => [
								'defaultValue' => 'Title',
								'required'     => false
							]
						]
					]),
					'tokens' => [
						'TEXT1' => 'title'
					],
					'passthroughToken' => 'TEXT2'
				]
			],
			[
				'[hr]',
				[
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null
				]
			],
			[
				'[hr][/hr]',
				[
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null
				]
			],
			[
				'[hr/]',
				[
					'name'   => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null
				]
			],
			[
				'[IMG src={URL;useContent}]',
				[
					'name'   => 'IMG',
					'bbcode' => new BBCode([
						'contentAttributes' => ['src'],
						'defaultAttribute'  => 'src'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'src' => [
								'filterChain' => [new Url]
							]
						]
					]),
					'tokens' => ['URL' => 'src'],
					'passthroughToken' => null
				]
			],
			[
				'[url={URL;useContent}]{TEXT}[/url]',
				[
					'name'   => 'URL',
					'bbcode' => new BBCode([
						'contentAttributes' => ['url'],
						'defaultAttribute'  => 'url'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new Url]
							]
						]
					]),
					'tokens' => [
						'URL'     => 'url'
					],
					'passthroughToken' => 'TEXT'
				]
			],
			[
				'[foo={INT;preFilter=strtolower,strtotime}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => ['strtolower', 'strtotime', new Int]
							]
						]
					]),
					'tokens' => [
						'INT' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={SIMPLETEXT;postFilter=strtolower,ucwords}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Simpletext, 'strtolower', 'ucwords']
							]
						]
					]),
					'tokens' => [
						'SIMPLETEXT' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={INT;postFilter=#identifier}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Int, new Identifier]
							]
						]
					]),
					'tokens' => [
						'INT' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={INT;preFilter=eval}/]',
				new RuntimeException("Filter 'eval' is not allowed")
			],
			[
				'[foo={INT;postFilter=eval}/]',
				new RuntimeException("Filter 'eval' is not allowed")
			],
			[
				'[foo={REGEXP=/^foo$/}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('/^foo$/')]
							]
						]
					]),
					'tokens' => [
						'REGEXP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={REGEXP=#^foo$#}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('#^foo$#')]
							]
						]
					]),
					'tokens' => [
						'REGEXP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={REGEXP=/[a-z]{3}\\//}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('/[a-z]{3}\\//')]
							]
						]
					]),
					'tokens' => [
						'REGEXP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/(?<foo>\\d+)/'],
							['foo', '/(?<bar>\\D+)/']
						],
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('/^(?:\\d+)$/D')]
							],
							'bar' => [
								'filterChain' => [new Regexp('/^(?:\\D+)$/D')]
							]
						]
					]),
					'tokens' => [],
					'passthroughToken' => null
				]
			],
			[
				'[foo={RANGE=-2,5}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Range(-2, 5)]
							]
						]
					]),
					'tokens' => [
						'RANGE' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={RANDOM=1000,9999}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'generator' => $this->getProgrammableCallback('mt_rand', 1000, 9999)
							]
						]
					]),
					'tokens' => [
						'RANDOM' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={CHOICE=one,two}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Choice(['one', 'two'])
								]
							]
						]
					]),
					'tokens' => [
						'CHOICE' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={CHOICE=pokémon,yugioh}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Choice(['pokémon', 'yugioh'])
								]
							]
						]
					]),
					'tokens' => [
						'CHOICE' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={CHOICE=Pokémon,YuGiOh;caseSensitive}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Choice(['Pokémon', 'YuGiOh'], true)
								]
							]
						]
					]),
					'tokens' => [
						'CHOICE' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=one:uno,two:dos}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Map([
										'one' => 'uno',
										'two' => 'dos'
									])
								]
							]
						]
					]),
					'tokens' => [
						'MAP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;caseSensitive}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Map(
										[
											'one' => 'uno',
											'two' => 'dos'
										],
										true,
										false
									)
								]
							]
						]
					]),
					'tokens' => [
						'MAP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;strict}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Map(
										[
											'one' => 'uno',
											'two' => 'dos'
										],
										false,
										true
									)
								]
							]
						]
					]),
					'tokens' => [
						'MAP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=pokémon:Pikachu,yugioh:Yugi}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Map([
										'pokémon' => 'Pikachu',
										'yugioh'  => 'Yugi'
									])
								]
							]
						]
					]),
					'tokens' => [
						'MAP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=Pokémon:Pikachu,YuGiOh:Yugi;caseSensitive}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new Map(
										[
											'Pokémon' => 'Pikachu',
											'YuGiOh'  => 'Yugi'
										],
										true
									)
								]
							]
						]
					]),
					'tokens' => [
						'MAP' => 'foo'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={NUMBER1},{NUMBER2} foo={NUMBER2};{NUMBER1}/]',
				[
					'name'   => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/^(?<foo0>\\d+),(?<foo1>\\d+)$/D'],
							['foo', '/^(?<foo1>\\d+);(?<foo0>\\d+)$/D']
						],
						'attributes' => [
							'foo0' => [
								'filterChain' => [new Number]
							],
							'foo1' => [
								'filterChain' => [new Number]
							]
						]
					]),
					'tokens' => [
						'NUMBER1' => 'foo0',
						'NUMBER2' => 'foo1'
					],
					'passthroughToken' => null
				]
			],
			[
				'[foo={MAP=foo:bar,baz}/]',
				new RuntimeException("Invalid map assignment 'baz'")
			],
			[
				/**
				* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=2127991
				*/
				'[flash={NUMBER1},{NUMBER2}]{URL}[/flash]',
				[
					'name'   => 'FLASH',
					'bbcode' => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'flash'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['flash', '/^(?<flash0>\\d+),(?<flash1>\\d+)$/D']
						],
						'attributes' => [
							'content' => [
								'filterChain' => [new Url]
							],
							'flash0' => [
								'filterChain' => [new Number]
							],
							'flash1' => [
								'filterChain' => [new Number]
							]
						]
					]),
					'tokens' => [
						'NUMBER1' => 'flash0',
						'NUMBER2' => 'flash1',
						'URL' => 'content'
					],
					'passthroughToken' => null
				]
			],
			[
				'[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2} url={URL;useContent}]',
				[
					'name'   => 'FLASH',
					'bbcode' => new BBCode([
						'contentAttributes' => ['url'],
						'defaultAttribute'  => 'flash'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['flash', '/^(?<width>\\d+),(?<height>\\d+)$/D']
						],
						'attributes' => [
							'url' => [
								'filterChain' => [new Url]
							],
							'width' => [
								'filterChain' => [new Number]
							],
							'height' => [
								'filterChain' => [new Number]
							]
						]
					]),
					'tokens' => [
						'NUMBER1' => 'width',
						'NUMBER2' => 'height',
						'URL' => 'url'
					],
					'passthroughToken' => null
				]
			],
			[
				/**
				* @link https://www.vbulletin.com/forum/misc.php?do=bbcode#quote
				*/
				'[quote={PARSE=/(?<author>.+?)(?:;(?<id>\\d+))?/} author={TEXT1;optional} id={UINT;optional}]{TEXT2}[/quote]',
				[
					'name'   => 'QUOTE',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'quote'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['quote', '/(?<author>.+?)(?:;(?<id>\\d+))?/']
						],
						'attributes' => [
							'author' => [
								'required' => false
							],
							'id'     => [
								'filterChain' => [new Uint],
								'required' => false
							]
						]
					]),
					'tokens' => [
						'TEXT1' => 'author',
						'UINT'  => 'id'
					],
					'passthroughToken' => 'TEXT2'
				]
			],
			[
				'[foo={PARSE=/bar/},{PARSE=/baz/}/]',
				new RuntimeException("{PARSE} tokens can only be used has the sole content of an attribute")
			],
			[
				// Here, we don't know to which attribute the token {INT} in attribute c correponds
				'[foo a={INT} b={INT} c={INT},{NUMBER} /]',
				new RuntimeException("Token {INT} used in attribute 'c' is ambiguous")
			],
			[
				'[foo={NUMBER},{NUMBER} /]',
				new RuntimeException("Token {NUMBER} used multiple times in attribute foo's definition")
			],
			[
				'[foo={PARSE=/(?<bar>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				new RuntimeException("Ambiguous attribute 'bar' created using different regexps needs to be explicitly defined")
			],
			[
				'[foo={PARSE}]',
				new RuntimeException("Malformed token 'PARSE'")
			],
			[
				'[foo={RANGE1}]',
				new RuntimeException("Malformed token 'RANGE1'")
			],
		];
	}

	public function getTemplateTests()
	{
		return [
			[
				'<b>{TEXT}</b>',
				[],
				'TEXT',
				'<b><xsl:apply-templates/></b>'
			],
			[
				'<b>{L_FOO}</b>',
				[],
				null,
				'<b><xsl:value-of select="$L_FOO"/></b>'
			],
			[
				'<b>{TEXT2}</b>',
				[],
				null,
				new RuntimeException('Token {TEXT2} is ambiguous or undefined')
			],
			[
				'<b>{NUMBER1}</b>',
				[],
				null,
				new RuntimeException('Token {NUMBER1} is ambiguous or undefined')
			],
			[
				'<span title="{TEXT}"/>',
				[],
				null,
				new RuntimeException('Token {TEXT} is ambiguous or undefined')
			],
			[
				'<span title="{NUMBER}"/>',
				[],
				null,
				new RuntimeException('Token {NUMBER} is ambiguous or undefined')
			],
			[
				'<span title="{L_FOO}">...</span>',
				[],
				null,
				'<span title="{$L_FOO}">...</span>'
			],
			[
				'<a href="{URL}">{TEXT}</a>',
				['URL' => 'url'],
				'TEXT',
				'<a href="{@url}"><xsl:apply-templates/></a>'
			],
			[
				'<b title="{TEXT}">{TEXT}</b>',
				[],
				'TEXT',
				'<b title="{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}"><xsl:apply-templates/></b>'
			],
			[
				'<span title="{ID}{ID}"/>',
				['ID' => 'id'],
				'TEXT',
				'<span title="{@id}{@id}"/>'
			],
			[
				'foo',
				[],
				'TEXT',
				'foo'
			],
			[
				'foo{TEXT}bar',
				[],
				'TEXT',
				'foo<xsl:apply-templates/>bar'
			],
			[
				'<hr><img src={IMG}><br>',
				['IMG' => 'url'],
				'TEXT',
				'<hr/><img src="{@url}"/><br/>',
			],
			[
				'</html><inv<alid',
				[],
				null,
				new RuntimeException('Invalid template')
			],
			[
				'',
				[],
				null,
				''
			],
			[
				'Hello {TEXT}',
				['TEXT' => 'username'],
				null,
				'Hello <xsl:value-of select="@username"/>'
			],
			[
				'<div>{TEXT1} {TEXT2}</div>',
				['TEXT1' => 'foo', 'TEXT2' => 'bar'],
				null,
				'<div><xsl:value-of select="@foo"/> <xsl:value-of select="@bar"/></div>'
			],
		];
	}

	protected function getProgrammableCallback()
	{
		$args = func_get_args();

		$programmableCallback = new ProgrammableCallback(array_shift($args));
		foreach ($args as $value)
		{
			$programmableCallback->addParameterByValue($value);
		}

		return $programmableCallback;
	}
}