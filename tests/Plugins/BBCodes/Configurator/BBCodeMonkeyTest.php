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
				'[föö]',
				new InvalidArgumentException("Invalid BBCode name 'föö'")
			),
			array(
				'[foo bar=TEXT]{TEXT}[/foo]',
				new RuntimeException("No tokens found in bar's definition")
			),
			array(
				'[foo bar={TEXT} bar={INT}]{TEXT}[/foo]',
				new RuntimeException("Attribute 'bar' is declared twice")
			),
			array(
				'[foo bar={TEXT} baz={TEXT}]{TEXT}[/foo]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'bar'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'bar' => array(),
							'baz' => array()
						)
					)),
					'tokens' => array(),
					'passthroughToken' => null
				)
			),
			array(
				'[URL={URL}]{TEXT}[/URL]',
				array(
					'name'   => 'URL',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'url'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							)
						)
					)),
					'tokens' => array(
						'URL' => 'url'
					),
					'passthroughToken' => 'TEXT'
				)
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
								'filterChain' => array(new Url)
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
								'filterChain' => array(new Url)
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
				'[foo={INT;preFilter=strtolower,strtotime}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array('strtolower', 'strtotime', new Int)
							)
						)
					)),
					'tokens' => array(
						'INT' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={SIMPLETEXT;postFilter=strtolower,ucwords}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Simpletext, 'strtolower', 'ucwords')
							)
						)
					)),
					'tokens' => array(
						'SIMPLETEXT' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={INT;postFilter=#identifier}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Int, new Identifier)
							)
						)
					)),
					'tokens' => array(
						'INT' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={INT;preFilter=eval}/]',
				new RuntimeException("Filter 'eval' is not allowed")
			),
			array(
				'[foo={INT;postFilter=eval}/]',
				new RuntimeException("Filter 'eval' is not allowed")
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
								'filterChain' => array(new Regexp('/^foo$/'))
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
								'filterChain' => array(new Regexp('#^foo$#'))
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
								'filterChain' => array(new Regexp('/[a-z]{3}\\//'))
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
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/(?<foo>\\d+)/'),
							array('foo', '/(?<bar>\\D+)/')
						),
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('/^(?:\\d+)$/D'))
							),
							'bar' => array(
								'filterChain' => array(new Regexp('/^(?:\\D+)$/D'))
							)
						)
					)),
					'tokens' => array(),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={RANGE=-2,5}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Range(-2, 5))
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
				'[foo={RANDOM=1000,9999}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'generator' => $this->getProgrammableCallback('mt_rand', 1000, 9999)
							)
						)
					)),
					'tokens' => array(
						'RANDOM' => 'foo'
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
									new Choice(array('one', 'two'))
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
									new Choice(array('pokémon', 'yugioh'))
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
									new Choice(array('Pokémon', 'YuGiOh'), true)
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
				'[foo={MAP=one:uno,two:dos}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(array(
										'one' => 'uno',
										'two' => 'dos'
									))
								)
							)
						)
					)),
					'tokens' => array(
						'MAP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={MAP=one:uno,two:dos;caseSensitive}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(
										array(
											'one' => 'uno',
											'two' => 'dos'
										),
										true,
										false
									)
								)
							)
						)
					)),
					'tokens' => array(
						'MAP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={MAP=one:uno,two:dos;strict}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(
										array(
											'one' => 'uno',
											'two' => 'dos'
										),
										false,
										true
									)
								)
							)
						)
					)),
					'tokens' => array(
						'MAP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={MAP=pokémon:Pikachu,yugioh:Yugi}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(array(
										'pokémon' => 'Pikachu',
										'yugioh'  => 'Yugi'
									))
								)
							)
						)
					)),
					'tokens' => array(
						'MAP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={MAP=Pokémon:Pikachu,YuGiOh:Yugi;caseSensitive}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(
										array(
											'Pokémon' => 'Pikachu',
											'YuGiOh'  => 'Yugi'
										),
										true
									)
								)
							)
						)
					)),
					'tokens' => array(
						'MAP' => 'foo'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={NUMBER1},{NUMBER2} foo={NUMBER2};{NUMBER1}/]',
				array(
					'name'   => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/^(?<foo0>\\d+),(?<foo1>\\d+)$/D'),
							array('foo', '/^(?<foo1>\\d+);(?<foo0>\\d+)$/D')
						),
						'attributes' => array(
							'foo0' => array(
								'filterChain' => array(new Number)
							),
							'foo1' => array(
								'filterChain' => array(new Number)
							)
						)
					)),
					'tokens' => array(
						'NUMBER1' => 'foo0',
						'NUMBER2' => 'foo1'
					),
					'passthroughToken' => null
				)
			),
			array(
				'[foo={MAP=foo:bar,baz}/]',
				new RuntimeException("Invalid map assignment 'baz'")
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
							array('flash', '/^(?<flash0>\\d+),(?<flash1>\\d+)$/D')
						),
						'attributes' => array(
							'content' => array(
								'filterChain' => array(new Url)
							),
							'flash0' => array(
								'filterChain' => array(new Number)
							),
							'flash1' => array(
								'filterChain' => array(new Number)
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
				'[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2} url={URL;useContent}]',
				array(
					'name'   => 'FLASH',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('url'),
						'defaultAttribute'  => 'flash'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('flash', '/^(?<width>\\d+),(?<height>\\d+)$/D')
						),
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							),
							'width' => array(
								'filterChain' => array(new Number)
							),
							'height' => array(
								'filterChain' => array(new Number)
							)
						)
					)),
					'tokens' => array(
						'NUMBER1' => 'width',
						'NUMBER2' => 'height',
						'URL' => 'url'
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
							array('quote', '/(?<author>.+?)(?:;(?<id>\\d+))?/')
						),
						'attributes' => array(
							'author' => array(
								'required' => false
							),
							'id'     => array(
								'filterChain' => array(new Uint),
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
			),
			array(
				'[foo={PARSE=/bar/},{PARSE=/baz/}/]',
				new RuntimeException("{PARSE} tokens can only be used has the sole content of an attribute")
			),
			array(
				// Here, we don't know to which attribute the token {INT} in attribute c correponds
				'[foo a={INT} b={INT} c={INT},{NUMBER} /]',
				new RuntimeException("Token {INT} used in attribute 'c' is ambiguous")
			),
			array(
				'[foo={NUMBER},{NUMBER} /]',
				new RuntimeException("Token {NUMBER} used multiple times in attribute foo's definition")
			),
			array(
				'[foo={PARSE=/(?<bar>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				new RuntimeException("Ambiguous attribute 'bar' created using different regexps needs to be explicitly defined")
			),
			array(
				'[foo={PARSE}]',
				new RuntimeException("Malformed token 'PARSE'")
			),
			array(
				'[foo={RANGE1}]',
				new RuntimeException("Malformed token 'RANGE1'")
			),
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
				'<hr><img src={IMG}><br>',
				array('IMG' => 'url'),
				'TEXT',
				'<hr/><img src="{@url}"/><br/>',
			),
			array(
				'</html><inv<alid',
				array(),
				null,
				new RuntimeException('Invalid template')
			),
			array(
				'',
				array(),
				null,
				''
			),
			array(
				'Hello {TEXT}',
				array('TEXT' => 'username'),
				null,
				'Hello <xsl:value-of select="@username"/>'
			)
		);
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