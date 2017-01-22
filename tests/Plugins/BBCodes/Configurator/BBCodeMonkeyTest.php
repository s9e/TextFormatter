<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\ChoiceFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\HashmapFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IdentifierFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IntFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\MapFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\NumberFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RangeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\SimpletextFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UintFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey
*/
class BBCodeMonkeyTest extends Test
{
	/**
	* @testdox $bbcodeMonkey->allowedFilters is a publicly-accessible list of callbacks
	*/
	public function testAllowedFiltersPublic()
	{
		$bbcodeMonkey   = new BBCodeMonkey(new Configurator);
		$allowedFilters = $bbcodeMonkey->allowedFilters;

		$this->assertInternalType('array', $allowedFilters);
		$this->assertContains('strrev', $allowedFilters);
	}

	/**
	* @testdox create() creates and return a BBCode, its name and its tag
	*/
	public function testCreateReturn()
	{
		$bm = new BBCodeMonkey(new Configurator);

		$this->assertEquals(
			[
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag([
					'template' => '<b><xsl:apply-templates/></b>'
				])
			],
			$bm->create('[FOO]{TEXT}[/FOO]', '<b>{TEXT}</b>')
		);
	}

	/**
	* @testdox create() accepts an instance of UnsafeTemplate as second argument
	*/
	public function testCreateUnsafeTemplate()
	{
		$bm = new BBCodeMonkey(new Configurator);
		$template = new UnsafeTemplate('<xsl:value-of select="." disable-output-escaping=""/>');

		$this->assertEquals(
			[
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag(['template' => $template])
			],
			$bm->create('[FOO]{TEXT}[/FOO]', $template)
		);
	}

	/**
	* @testdox create() tests
	* @dataProvider getCreateTests
	*/
	public function testCreate($usage, $template, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$bbcodeMonkey = new BBCodeMonkey(new Configurator);
		$actual = $bbcodeMonkey->create($usage, $template);

		if (!($expected instanceof Exception))
		{
			$this->assertEquals($expected, $actual);
		}
	}

	public function getCreateTests()
	{
		return [
			[
				'*invalid*',
				'',
				new InvalidArgumentException('Cannot interpret the BBCode definition')
			],
			[
				'[föö]',
				'',
				new InvalidArgumentException("Invalid BBCode name 'föö'")
			],
			[
				'[foo bar=TEXT]{TEXT}[/foo]',
				'',
				new RuntimeException("No valid tokens found in bar's definition")
			],
			[
				'[foo bar={TEXT} bar={INT}]{TEXT}[/foo]',
				'',
				new RuntimeException("Attribute 'bar' is declared twice")
			],
			[
				'[foo bar={TEXT} baz={TEXT}]{TEXT}[/foo]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'bar'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'bar' => [],
							'baz' => []
						],
						'template' => ''
					])
				]
			],
			[
				'[URL={URL}]{TEXT}[/URL]',
				'{TEXT}',
				[
					'bbcodeName' => 'URL',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'url'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template' => '<xsl:apply-templates/>'
					])
				]
			],
			[
				'[URL]{URL}[/URL]',
				'{URL}',
				[
					'bbcodeName' => 'URL',
					'bbcode'     => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'content'
					]),
					'tag'        => new Tag([
						'attributes' => [
							'content' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template' => '<xsl:value-of select="@content"/>'
					])
				]
			],
			[
				'[b]{TEXT}[/B]',
				'<b>{TEXT}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => '<b><xsl:apply-templates/></b>'])
				]
			],
			[
				'[b]{ANYTHING}[/B]',
				'<b>{ANYTHING}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => '<b><xsl:apply-templates/></b>'])
				]
			],
			[
				'[b]{ANYTHING2}[/B]',
				'<b>{ANYTHING2}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => '<b><xsl:apply-templates/></b>'])
				]
			],
			[
				'[b title={TEXT1}]{TEXT2}[/B]',
				'<b title="{TEXT1}">{TEXT2}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'title'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'title' => []
						],
						'template' => '<b title="{@title}"><xsl:apply-templates/></b>'
					])
				]
			],
			[
				'[b title={TEXT1;optional;required;optional}]{TEXT2}[/B]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'title'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'title' => []
						],
						'template' => ''
					])
				]
			],
			[
				'[b title={TEXT1;defaultValue=Title;optional}]{TEXT2}[/B]',
				'',
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
						],
						'template' => ''
					])
				]
			],
			[
				'[hr]',
				'',
				[
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[hr][/hr]',
				'',
				[
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[hr/]',
				'',
				[
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[IMG src={URL;useContent}]',
				'',
				[
					'bbcodeName' => 'IMG',
					'bbcode' => new BBCode([
						'contentAttributes' => ['src'],
						'defaultAttribute'  => 'src'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'src' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[url={URL;useContent}]{TEXT}[/url]',
				'<a href="{URL}">{TEXT}</a>',
				[
					'bbcodeName' => 'URL',
					'bbcode' => new BBCode([
						'contentAttributes' => ['url'],
						'defaultAttribute'  => 'url'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template' => '<a href="{@url}"><xsl:apply-templates/></a>'
					])
				]
			],
			[
				'[foo={INT;preFilter=strtolower,strtotime}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => ['strtolower', 'strtotime', new IntFilter]
							]
						],
						'template' => ''
					])	
				]
			],
			[
				'[foo={SIMPLETEXT;postFilter=strtolower,ucwords}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new SimpletextFilter, 'strtolower', 'ucwords']
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={INT;postFilter=#identifier}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new IntFilter, new IdentifierFilter]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={INT;preFilter=eval}/]',
				'',
				new RuntimeException("Filter 'eval' is not allowed")
			],
			[
				'[foo={INT;postFilter=eval}/]',
				'',
				new RuntimeException("Filter 'eval' is not allowed")
			],
			[
				'[foo={REGEXP=/^foo$/}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new RegexpFilter('/^foo$/')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={REGEXP=#^foo$#}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new RegexpFilter('#^foo$#')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={REGEXP=#^foo$#iusDSU}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new RegexpFilter('#^foo$#iusDSU')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={REGEXP=/[a-z]{3}\\//}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new RegexpFilter('/[a-z]{3}\\//')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[anchor={REGEXP=/^#?[a-z][-a-z_0-9]{0,}$/i}]{TEXT}[/anchor]',
				'',
				[
					'bbcodeName' => 'ANCHOR',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'anchor'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'anchor' => [
								'filterChain' => [new RegexpFilter('/^#?[a-z][-a-z_0-9]{0,}$/i')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={PARSE=/\\/\\{(?<bar>.)\\}\\//}]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/\\/\\{(?<bar>.)\\}\\//']
						],
						'attributes' => [
							'bar' => [
								'filterChain' => [new RegexpFilter('/^.$/D')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				'',
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
								'filterChain' => [new RegexpFilter('/^\\d+$/D')]
							],
							'bar' => [
								'filterChain' => [new RegexpFilter('/^\\D+$/D')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={PARSE=/(?<foo>\\d+)/uD}]',
				'',
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
								'filterChain' => [new RegexpFilter('/^\\d+$/uD')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/,/(?<bar>\\D+)/}]',
				'',
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
								'filterChain' => [new RegexpFilter('/^\\d+$/D')]
							],
							'bar' => [
								'filterChain' => [new RegexpFilter('/^\\D+$/D')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={PARSE=/,\\/(?<foo>\\d+)/u,/,(?<bar>\\D+)\\/,/u}]',
				'',
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
								'filterChain' => [new RegexpFilter('/^\\d+$/uD')]
							],
							'bar' => [
								'filterChain' => [new RegexpFilter('/^\\D+$/uD')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[name={PARSE=/(?<first>\w+) (?<last>\w+)/,/(?<last>\w+), (?<first>\w+)/}]',
				'',
				[
					'bbcodeName' => 'NAME',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'name'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['name', '/(?<first>\w+) (?<last>\w+)/'],
							['name', '/(?<last>\w+), (?<first>\w+)/']
						],
						'attributes' => [
							'first' => [
								'filterChain' => [new RegexpFilter('/^\\w+$/D')]
							],
							'last' => [
								'filterChain' => [new RegexpFilter('/^\\w+$/D')]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={RANGE=-2,5}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [new RangeFilter(-2, 5)]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={RANDOM=1000,9999}/]',
				'',
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
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={CHOICE=one,two}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new ChoiceFilter(['one', 'two'])
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={CHOICE=pokémon,yugioh}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new ChoiceFilter(['pokémon', 'yugioh'])
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={CHOICE=Pokémon,YuGiOh;caseSensitive}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new ChoiceFilter(['Pokémon', 'YuGiOh'], true)
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=one:uno,two:dos}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter([
										'one' => 'uno',
										'two' => 'dos'
									])
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;caseSensitive}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter(
										[
											'one' => 'uno',
											'two' => 'dos'
										],
										true,
										false
									)
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=one:uno,two:dos;strict}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter(
										[
											'one' => 'uno',
											'two' => 'dos'
										],
										false,
										true
									)
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=pokémon:Pikachu,yugioh:Yugi}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter([
										'pokémon' => 'Pikachu',
										'yugioh'  => 'Yugi'
									])
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=Pokémon:Pikachu,YuGiOh:Yugi;caseSensitive}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter(
										[
											'Pokémon' => 'Pikachu',
											'YuGiOh'  => 'Yugi'
										],
										true
									)
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={MAP=sans serif:sans-serif,sansserif:sans-serif}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new MapFilter([
										'sans serif' => 'sans-serif',
										'sansserif'  => 'sans-serif'
									])
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={NUMBER}/x]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/^(?<foo0>\\d+)\\/x$/D']
						],
						'attributes' => [
							'foo0' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={NUMBER1},{NUMBER2} foo={NUMBER2};{NUMBER1}/]',
				'{NUMBER1}{NUMBER2}',
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
								'filterChain' => [new NumberFilter]
							],
							'foo1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => '<xsl:value-of select="@foo0"/><xsl:value-of select="@foo1"/>'
					])
				]
			],
			[
				'[foo={MAP=foo:bar,baz}/]',
				'',
				new RuntimeException("Invalid map assignment 'baz'")
			],
			[
				'[foo={HASHMAP=one:uno,two:dos}/]',
				'{HASHMAP}',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new HashmapFilter([
										'one' => 'uno',
										'two' => 'dos'
									])
								]
							]
						],
						'template' => '<xsl:value-of select="@foo"/>'
					])
				]
			],
			[
				'[foo={HASHMAP=one:uno,two:dos;strict}/]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => [
								'filterChain' => [
									new HashmapFilter(
										[
											'one' => 'uno',
											'two' => 'dos'
										],
										true
									)
								]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={HASHMAP=foo:bar,baz}/]',
				'',
				new RuntimeException("Invalid map assignment 'baz'")
			],
			[
				/**
				* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=2127991
				*/
				'[flash={NUMBER1},{NUMBER2}]{URL}[/flash]',
				'<object width="{NUMBER1}" height="{NUMBER2}"/>',
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
								'filterChain' => [new UrlFilter]
							],
							'flash0' => [
								'filterChain' => [new NumberFilter]
							],
							'flash1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => '<object width="{@flash0}" height="{@flash1}"/>'
					])
				]
			],
			[
				'[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2} url={URL;useContent}]',
				'<object width="{NUMBER1}" height="{NUMBER2}"/>',
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
								'filterChain' => [new UrlFilter]
							],
							'width' => [
								'filterChain' => [new NumberFilter]
							],
							'height' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => '<object width="{@width}" height="{@height}"/>'
					])
				]
			],
			[
				'[foo={TEXT1},{TEXT2}]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/^(?<foo0>.*?),(?<foo1>.*?)$/D']
						],
						'attributes' => [
							'foo0' => [],
							'foo1' => []
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={ANYTHING1},{ANYTHING2}]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/^(?<foo0>.*?),(?<foo1>.*?)$/D']
						],
						'attributes' => [
							'foo0' => [],
							'foo1' => []
						],
						'template' => ''
					])
				]
			],
			[
				/**
				* @link https://www.vbulletin.com/forum/misc.php?do=bbcode#quote
				*/
				'[quote={PARSE=/(?<author>.+?)(?:;(?<id>\\d+))?/} author={TEXT1;optional} id={UINT;optional}]{TEXT2}[/quote]',
				'',
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
								'filterChain' => [new UintFilter],
								'required' => false
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo={PARSE=/bar/},{PARSE=/baz/}/]',
				'',
				new RuntimeException("{PARSE} tokens can only be used has the sole content of an attribute")
			],
			[
				// Here, we don't know to which attribute the token {INT} in attribute c correponds
				'[foo a={INT} b={INT} c={INT},{NUMBER} /]',
				'',
				new RuntimeException("Token {INT} used in attribute 'c' is ambiguous")
			],
			[
				'[foo={NUMBER},{NUMBER} /]',
				'',
				new RuntimeException("Token {NUMBER} used multiple times in attribute foo's definition")
			],
			[
				'[foo={PARSE=/(?<bar>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				'',
				new RuntimeException("Ambiguous attribute 'bar' created using different regexps needs to be explicitly defined")
			],
			[
				'[foo={PARSE}]',
				'',
				new RuntimeException("Malformed token 'PARSE'")
			],
			[
				'[foo={RANGE1}]',
				'',
				new RuntimeException("Malformed token 'RANGE1'")
			],
			[
				'[foo]{NUMBER1},{NUMBER2}[/foo]',
				'',
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
								'filterChain' => [new NumberFilter]
							],
							'content1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo]{NUMBER1} * {NUMBER2}[/foo]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'contentAttributes' => ['content'],
						'defaultAttribute'  => 'content'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['content', '/^(?<content0>\\d+)\\s+\\*\\s+(?<content1>\\d+)$/D']
						],
						'attributes' => [
							'content0' => [
								'filterChain' => [new NumberFilter]
							],
							'content1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => ''
					])
				]
			],
			[
				'[foo $tagName=bar]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode'     => new BBCode(['tagName' => 'BAR']),
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[B $forceLookahead=false]{TEXT}[/B]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['forceLookahead' => false]),
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[B $forceLookahead=true]{TEXT}[/B]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['forceLookahead' => true]),
					'tag'        => new Tag(['template' => ''])
				]
			],
			[
				'[B #autoReopen=false]{TEXT}[/B]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'rules'    => [
							'autoReopen' => false,
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						],
						'template' => ''
					])
				]
			],
			[
				'[B #autoReopen=true]{TEXT}[/B]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'rules'    => [
							'autoReopen' => true,
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						],
						'template' => ''
					])
				]
			],
			[
				'[X #closeParent=X #closeParent=Y]',
				'',
				[
					'bbcodeName' => 'X',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'rules'    => [
							'closeParent' => ['X', 'Y'],
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						],
						'template' => ''
					])
				]
			],
			[
				'[X #closeParent=X,Y]',
				'',
				[
					'bbcodeName' => 'X',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'rules'    => [
							'closeParent' => ['X', 'Y'],
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						],
						'template' => ''
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'<b>{L_FOO}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'template' => '<b><xsl:value-of select="$L_FOO"/></b>'
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'<b>{TEXT2}</b>',
				new RuntimeException('Token {TEXT2} is ambiguous or undefined')
			],
			[
				'[b]{TEXT}[/b]',
				'<b>{NUMBER1}</b>',
				new RuntimeException('Token {NUMBER1} is ambiguous or undefined')
			],
			[
				'[x]{NUMBER}[/x]',
				'<span title="{TEXT}"/>',
				new RuntimeException('Token {TEXT} is ambiguous or undefined')
			],
			[
				'[b]{TEXT}[/b]',
				'<span title="{NUMBER}"/>',
				new RuntimeException('Token {NUMBER} is ambiguous or undefined')
			],
			[
				'[b]{TEXT}[/b]',
				'<span title="{L_FOO}">...</span>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'template' => '<span title="{$L_FOO}">...</span>'
					])
				]
			],
			[
				'[url={URL}]{TEXT}[/url]',
				'<a href="{URL}">{TEXT}</a>',
				[
					'bbcodeName' => 'URL',
					'bbcode'     => new BBCode([
						'defaultAttribute' => 'url'
					]),
					'tag'        => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template'   => '<a href="{@url}"><xsl:apply-templates/></a>'
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'<b title="{TEXT}">{TEXT}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode	,
					'tag'        => new Tag([
						'template' => '<b title="{.}"><xsl:apply-templates/></b>'
					])
				]
			],
			[
				'[b id={IDENTIFIER}]{TEXT}[/b]',
				'<span title="{IDENTIFIER}{IDENTIFIER}"/>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['defaultAttribute' => 'id']),
					'tag'        => new Tag([
						'attributes' => [
							'id' => [
								'filterChain' => [new IdentifierFilter]
							]
						],
						'template'   => '<span title="{@id}{@id}"/>'
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'foo',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'template' => 'foo'
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'foo{TEXT}bar',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'template' => 'foo<xsl:apply-templates/>bar'
					])
				]
			],
			[
				'[b url={URL}]{TEXT}[/b]',
				'<hr><img src={URL}><br>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['defaultAttribute' => 'url']),
					'tag'        => new Tag([
						'attributes' => [
							'url' => [
								'filterChain' => [new UrlFilter]
							]
						],
						'template' => '<hr/><img src="{@url}"/><br/>'
					])
				]
			],
			[
				'[b]{TEXT}[/b]',
				'',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag([
						'template' => ''
					])
				]
			],
			[
				'[b username={TEXT}]',
				'Hello {TEXT}',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['defaultAttribute' => 'username']),
					'tag'        => new Tag([
						'attributes' => [
							'username' => []
						],
						'template' => 'Hello <xsl:value-of select="@username"/>'
					])
				]
			],
			[
				'[b foo={TEXT1} bar={TEXT2}]',
				'<div>{TEXT1} {TEXT2}</div>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['defaultAttribute' => 'foo']),
					'tag'        => new Tag([
						'attributes' => [
							'foo' => [],
							'bar' => []
						],
						'template' => '<div><xsl:value-of select="@foo"/> <xsl:value-of select="@bar"/></div>'
					])
				]
			],
			[
				'[b foo={TEXT}]',
				'<b>{@foo}</div>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(['defaultAttribute' => 'foo']),
					'tag'        => new Tag([
						'attributes' => [
							'foo' => []
						],
						'template' => '<b><xsl:value-of select="@foo"/></b>'
					])
				]
			],
			[
				'[B]
					{TEXT}
				[/B]',
				'<b>{TEXT}</b>',
				[
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(['template' => '<b><xsl:apply-templates/></b>'])
				]
			],
			[
				'[X foo={TEXT}
					bar={TEXT}
				]
					{TEXT}
				[/X]',
				'',
				[
					'bbcodeName' => 'X',
					'bbcode'     => new BBCode(['defaultAttribute' => 'foo']),
					'tag'        => new Tag([
						'attributes' => [
							'foo' => [],
							'bar' => []
						],
						'template' => ''
					])
				]
			],
			[
				'[foo="{TEXT1}"]{TEXT2}[/foo]',
				'',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute' => 'foo'
					]),
					'tag'    => new Tag([
						'attributes' => [
							'foo' => []
						],
						'template' => ''
					])
				]
			],
			[
				'[foo="{NUMBER1},{NUMBER2}" foo="{NUMBER2};{NUMBER1}"/]',
				'{NUMBER1}{NUMBER2}',
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
								'filterChain' => [new NumberFilter]
							],
							'foo1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => '<xsl:value-of select="@foo0"/><xsl:value-of select="@foo1"/>'
					])
				]
			],
			[
				'[foo={NUMBER1} {NUMBER2}]',
				'{NUMBER1}{NUMBER2}',
				[
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode([
						'defaultAttribute'  => 'foo'
					]),
					'tag'    => new Tag([
						'attributePreprocessors' => [
							['foo', '/^(?<foo0>\\d+)\\s+(?<foo1>\\d+)$/D']
						],
						'attributes' => [
							'foo0' => [
								'filterChain' => [new NumberFilter]
							],
							'foo1' => [
								'filterChain' => [new NumberFilter]
							]
						],
						'template' => '<xsl:value-of select="@foo0"/><xsl:value-of select="@foo1"/>'
					])
				]
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