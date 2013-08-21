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

	/**
	* @testdox create() creates and return a BBCode, its name and its tag
	*/
	public function testCreate()
	{
		$bm = new BBCodeMonkey(new Configurator);

		$this->assertEquals(
			[
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag([
					'defaultTemplate' => '<b><xsl:apply-templates/></b>'
				])
			],
			$bm->create('[FOO]{TEXT}[/FOO]', '<b>{TEXT}</b>')
		);
	}

	/**
	* @testdox create() accepts an array of [predicate => template] as second argument
	*/
	public function testCreateMultipleTemplates()
	{
		$bm = new BBCodeMonkey(new Configurator);

		$this->assertEquals(
			[
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag([
					'templates' => [
						''            => '<b><xsl:apply-templates/></b>',
						'parent::BAR' => '<i><xsl:apply-templates/></i>'
					]
				])
			],
			$bm->create(
				'[FOO]{TEXT}[/FOO]',
				[
						''            => '<b>{TEXT}</b>',
						'parent::BAR' => '<i>{TEXT}</i>'
				]
			)
		);
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
				new RuntimeException("No valid tokens found in bar's definition")
			],
			[
				'[foo bar={TEXT} bar={INT}]{TEXT}[/foo]',
				new RuntimeException("Attribute 'bar' is declared twice")
			],
			[
				'[foo bar={TEXT} baz={TEXT}]{TEXT}[/foo]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[URL={URL}]{TEXT}[/URL]',
				[
					'bbcodeName' => 'URL',
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
					'passthroughToken' => 'TEXT',
					'rules' => []
				]
			],
			[
				'[URL]{URL}[/URL]',
				[
					'bbcodeName' => 'URL',
					'bbcode' => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'content'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'content' => [
								'filterChain' => [new Url]
							]
						]
					]),
					'tokens' => [
						'URL' => 'content'
					],
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[b]{TEXT}[/B]',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag,
					'tokens'     => [],
					'passthroughToken' => 'TEXT',
					'rules'      => []
				]
			],
			[
				'[b]{ANYTHING}[/B]',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag,
					'tokens'     => [],
					'passthroughToken' => 'ANYTHING',
					'rules'      => []
				]
			],
			[
				'[b]{ANYTHING2}[/B]',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag,
					'tokens'     => [],
					'passthroughToken' => 'ANYTHING2',
					'rules'      => []
				]
			],
			[
				'[b title={TEXT1}]{TEXT2}[/B]',
				[
					'bbcodeName' => 'B',
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
					'passthroughToken' => 'TEXT2',
					'rules' => []
				]
			],
			[
				'[b title={TEXT1;optional;required;optional}]{TEXT2}[/B]',
				[
					'bbcodeName' => 'B',
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
					'passthroughToken' => 'TEXT2',
					'rules' => []
				]
			],
			[
				'[b title={TEXT1;defaultValue=Title;optional}]{TEXT2}[/B]',
				[
					'bbcodeName' => 'B',
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
					'passthroughToken' => 'TEXT2',
					'rules' => []
				]
			],
			[
				'[hr]',
				[
					'bbcodeName' => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null,
					'rules'  => []
				]
			],
			[
				'[hr][/hr]',
				[
					'bbcodeName' => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null,
					'rules'  => []
				]
			],
			[
				'[hr/]',
				[
					'bbcodeName' => 'HR',
					'bbcode' => new BBCode,
					'tag'    => new Tag,
					'tokens' => [],
					'passthroughToken' => null,
					'rules'  => []
				]
			],
			[
				'[IMG src={URL;useContent}]',
				[
					'bbcodeName' => 'IMG',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[url={URL;useContent}]{TEXT}[/url]',
				[
					'bbcodeName' => 'URL',
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
						'URL' => 'url'
					],
					'passthroughToken' => 'TEXT',
					'rules' => []
				]
			],
			[
				'[foo={INT;preFilter=strtolower,strtotime}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={SIMPLETEXT;postFilter=strtolower,ucwords}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={INT;postFilter=#identifier}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
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
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={REGEXP=#^foo$#}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={REGEXP=#^foo$#iusDSU}/]',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('#^foo$#iusDSU')]
							]
						]
					]),
					'tokens' => [
						'REGEXP' => 'foo'
					],
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={REGEXP=/[a-z]{3}\\//}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={PARSE=/(?<foo>\\d+)/uD}]',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/(?<foo>\\d+)/uD'],
						],
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('/^(?:\\d+)$/uD')]
							]
						]
					]),
					'tokens' => [],
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/,/(?<bar>\\D+)/}]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={PARSE=/,\\/(?<foo>\\d+)/u,/,(?<bar>\\D+)\\/,/u}]',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/,\\/(?<foo>\\d+)/u'],
							['foo', '/,(?<bar>\\D+)\\/,/u']
						],
						'attributes' => [
							'foo' => [
								'filterChain' => [new Regexp('/^(?:\\d+)$/uD')]
							],
							'bar' => [
								'filterChain' => [new Regexp('/^(?:\\D+)$/uD')]
							]
						]
					]),
					'tokens' => [],
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={RANGE=-2,5}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={RANDOM=1000,9999}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={CHOICE=one,two}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={CHOICE=pokémon,yugioh}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={CHOICE=Pokémon,YuGiOh;caseSensitive}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={MAP=one:uno,two:dos}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;caseSensitive}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;strict}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={MAP=pokémon:Pikachu,yugioh:Yugi}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={MAP=Pokémon:Pikachu,YuGiOh:Yugi;caseSensitive}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo={NUMBER1},{NUMBER2} foo={NUMBER2};{NUMBER1}/]',
				[
					'bbcodeName' => 'FOO',
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
					'passthroughToken' => null,
					'rules' => []
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
					'bbcodeName' => 'FLASH',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2} url={URL;useContent}]',
				[
					'bbcodeName' => 'FLASH',
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
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				/**
				* @link https://www.vbulletin.com/forum/misc.php?do=bbcode#quote
				*/
				'[quote={PARSE=/(?<author>.+?)(?:;(?<id>\\d+))?/} author={TEXT1;optional} id={UINT;optional}]{TEXT2}[/quote]',
				[
					'bbcodeName' => 'QUOTE',
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
					'passthroughToken' => 'TEXT2',
					'rules' => []
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
			[
				'[foo]{NUMBER1},{NUMBER2}[/foo]',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'content'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['content', '/^(?<content0>\\d+),(?<content1>\\d+)$/D']
						],
						'attributes' => [
							'content0' => [
								'filterChain' => [new Number]
							],
							'content1' => [
								'filterChain' => [new Number]
							]
						]
					]),
					'tokens' => [
						'NUMBER1' => 'content0',
						'NUMBER2' => 'content1'
					],
					'passthroughToken' => null,
					'rules' => []
				]
			],
			[
				'[foo]{NUMBER1} * {NUMBER2}[/foo]',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'content'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['content', '/^(?<content0>\\d+) \\* (?<content1>\\d+)$/D']
						],
						'attributes' => [
							'content0' => [
								'filterChain' => [new Number]
							],
							'content1' => [
								'filterChain' => [new Number]
							]
						]
					]),
					'tokens' => [
						'NUMBER1' => 'content0',
						'NUMBER2' => 'content1'
					],
					'passthroughToken' => null,
					'rules' => []
				]
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