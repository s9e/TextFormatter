<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Choice;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap;
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
			array(
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag(array(
					'template' => '<b><xsl:apply-templates/></b>'
				))
			),
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
			array(
				'bbcodeName' => 'FOO',
				'bbcode'     => new BBCode,
				'tag'        => new Tag(array('template' => $template))
			),
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
		return array(
			array(
				'*invalid*',
				'',
				new InvalidArgumentException('Cannot interpret the BBCode definition')
			),
			array(
				'[föö]',
				'',
				new InvalidArgumentException("Invalid BBCode name 'föö'")
			),
			array(
				'[foo bar=TEXT]{TEXT}[/foo]',
				'',
				new RuntimeException("No valid tokens found in bar's definition")
			),
			array(
				'[foo bar={TEXT} bar={INT}]{TEXT}[/foo]',
				'',
				new RuntimeException("Attribute 'bar' is declared twice")
			),
			array(
				'[foo bar={TEXT} baz={TEXT}]{TEXT}[/foo]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'bar'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'bar' => array(),
							'baz' => array()
						),
						'template' => ''
					))
				)
			),
			array(
				'[URL={URL}]{TEXT}[/URL]',
				'{TEXT}',
				array(
					'bbcodeName' => 'URL',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'url'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							)
						),
						'template' => '<xsl:apply-templates/>'
					))
				)
			),
			array(
				'[URL]{URL}[/URL]',
				'{URL}',
				array(
					'bbcodeName' => 'URL',
					'bbcode'     => new BBCode(array(
						'contentAttributes' => array('content'),
						'defaultAttribute'  => 'content'
					)),
					'tag'        => new Tag(array(
						'attributes' => array(
							'content' => array(
								'filterChain' => array(new Url)
							)
						),
						'template' => '<xsl:value-of select="@content"/>'
					))
				)
			),
			array(
				'[b]{TEXT}[/B]',
				'<b>{TEXT}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => '<b><xsl:apply-templates/></b>'))
				)
			),
			array(
				'[b]{ANYTHING}[/B]',
				'<b>{ANYTHING}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => '<b><xsl:apply-templates/></b>'))
				)
			),
			array(
				'[b]{ANYTHING2}[/B]',
				'<b>{ANYTHING2}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => '<b><xsl:apply-templates/></b>'))
				)
			),
			array(
				'[b title={TEXT1}]{TEXT2}[/B]',
				'<b title="{TEXT1}">{TEXT2}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array()
						),
						'template' => '<b title="{@title}"><xsl:apply-templates/></b>'
					))
				)
			),
			array(
				'[b title={TEXT1;optional;required;optional}]{TEXT2}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array()
						),
						'template' => ''
					))
				)
			),
			array(
				'[b title={TEXT1;defaultValue=Title;optional}]{TEXT2}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode' => new BBCode(array(
						'defaultAttribute' => 'title'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'title' => array(
								'defaultValue' => 'Title',
								'required'     => false
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[hr]',
				'',
				array(
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[hr][/hr]',
				'',
				array(
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[hr/]',
				'',
				array(
					'bbcodeName' => 'HR',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[IMG src={URL;useContent}]',
				'',
				array(
					'bbcodeName' => 'IMG',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('src'),
						'defaultAttribute'  => 'src'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'src' => array(
								'filterChain' => array(new Url)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[url={URL;useContent}]{TEXT}[/url]',
				'<a href="{URL}">{TEXT}</a>',
				array(
					'bbcodeName' => 'URL',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('url'),
						'defaultAttribute'  => 'url'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							)
						),
						'template' => '<a href="{@url}"><xsl:apply-templates/></a>'
					))
				)
			),
			array(
				'[foo={INT;preFilter=strtolower,strtotime}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array('strtolower', 'strtotime', new Int)
							)
						),
						'template' => ''
					))	
				)
			),
			array(
				'[foo={SIMPLETEXT;postFilter=strtolower,ucwords}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Simpletext, 'strtolower', 'ucwords')
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={INT;postFilter=#identifier}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Int, new Identifier)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={INT;preFilter=eval}/]',
				'',
				new RuntimeException("Filter 'eval' is not allowed")
			),
			array(
				'[foo={INT;postFilter=eval}/]',
				'',
				new RuntimeException("Filter 'eval' is not allowed")
			),
			array(
				'[foo={REGEXP=/^foo$/}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('/^foo$/'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={REGEXP=#^foo$#}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('#^foo$#'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={REGEXP=#^foo$#iusDSU}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('#^foo$#iusDSU'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={REGEXP=/[a-z]{3}\\//}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('/[a-z]{3}\\//'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[anchor={REGEXP=/^#?[a-z][-a-z_0-9]{0,}$/i}]{TEXT}[/anchor]',
				'',
				array(
					'bbcodeName' => 'ANCHOR',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'anchor'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'anchor' => array(
								'filterChain' => array(new Regexp('/^#?[a-z][-a-z_0-9]{0,}$/i'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={PARSE=/\\/\\{(?<bar>.)\\}\\//}]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/\\/\\{(?<bar>.)\\}\\//')
						),
						'attributes' => array(
							'bar' => array(
								'filterChain' => array(new Regexp('/^(?:.)$/D'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={PARSE=/(?<foo>\\d+)/uD}]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/(?<foo>\\d+)/uD'),
						),
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('/^(?:\\d+)$/uD'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				// Ensure that every subpattern creates an attribute with the corresponding regexp
				'[foo={PARSE=/(?<foo>\\d+)/,/(?<bar>\\D+)/}]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={PARSE=/,\\/(?<foo>\\d+)/u,/,(?<bar>\\D+)\\/,/u}]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/,\\/(?<foo>\\d+)/u'),
							array('foo', '/,(?<bar>\\D+)\\/,/u')
						),
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Regexp('/^(?:\\d+)$/uD'))
							),
							'bar' => array(
								'filterChain' => array(new Regexp('/^(?:\\D+)$/uD'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[name={PARSE=/(?<first>\w+) (?<last>\w+)/,/(?<last>\w+), (?<first>\w+)/}]',
				'',
				array(
					'bbcodeName' => 'NAME',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'name'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('name', '/(?<first>\w+) (?<last>\w+)/'),
							array('name', '/(?<last>\w+), (?<first>\w+)/')
						),
						'attributes' => array(
							'first' => array(
								'filterChain' => array(new Regexp('/^(?:\\w+)$/D'))
							),
							'last' => array(
								'filterChain' => array(new Regexp('/^(?:\\w+)$/D'))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={RANGE=-2,5}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(new Range(-2, 5))
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={RANDOM=1000,9999}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'generator' => $this->getProgrammableCallback('mt_rand', 1000, 9999)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={CHOICE=one,two}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={CHOICE=pokémon,yugioh}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={CHOICE=Pokémon,YuGiOh;caseSensitive}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=one:uno,two:dos}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=one:uno,two:dos;caseSensitive}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=one:uno,two:dos;strict}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=pokémon:Pikachu,yugioh:Yugi}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=Pokémon:Pikachu,YuGiOh:Yugi;caseSensitive}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={MAP=sans serif:sans-serif,sansserif:sans-serif}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Map(array(
										'sans serif' => 'sans-serif',
										'sansserif'  => 'sans-serif'
									))
								)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={NUMBER}/x]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/^(?<foo0>\\d+)\\/x$/D')
						),
						'attributes' => array(
							'foo0' => array(
								'filterChain' => array(new Number)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={NUMBER1},{NUMBER2} foo={NUMBER2};{NUMBER1}/]',
				'{NUMBER1}{NUMBER2}',
				array(
					'bbcodeName' => 'FOO',
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
						),
						'template' => '<xsl:value-of select="@foo0"/><xsl:value-of select="@foo1"/>'
					))
				)
			),
			array(
				'[foo={MAP=foo:bar,baz}/]',
				'',
				new RuntimeException("Invalid map assignment 'baz'")
			),
			array(
				'[foo={HASHMAP=one:uno,two:dos}/]',
				'{HASHMAP}',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Hashmap(array(
										'one' => 'uno',
										'two' => 'dos'
									))
								)
							)
						),
						'template' => '<xsl:value-of select="@foo"/>'
					))
				)
			),
			array(
				'[foo={HASHMAP=one:uno,two:dos;strict}/]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributes' => array(
							'foo' => array(
								'filterChain' => array(
									new Hashmap(
										array(
											'one' => 'uno',
											'two' => 'dos'
										),
										true
									)
								)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={HASHMAP=foo:bar,baz}/]',
				'',
				new RuntimeException("Invalid map assignment 'baz'")
			),
			array(
				/**
				* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=2127991
				*/
				'[flash={NUMBER1},{NUMBER2}]{URL}[/flash]',
				'<object width="{NUMBER1}" height="{NUMBER2}"/>',
				array(
					'bbcodeName' => 'FLASH',
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
						),
						'template' => '<object width="{@flash0}" height="{@flash1}"/>'
					))
				)
			),
			array(
				'[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2} url={URL;useContent}]',
				'<object width="{NUMBER1}" height="{NUMBER2}"/>',
				array(
					'bbcodeName' => 'FLASH',
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
						),
						'template' => '<object width="{@width}" height="{@height}"/>'
					))
				)
			),
			array(
				'[foo={TEXT1},{TEXT2}]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'defaultAttribute'  => 'foo'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('foo', '/^(?<foo0>.+?),(?<foo1>.+?)$/D')
						),
						'attributes' => array(
							'foo0' => array(),
							'foo1' => array()
						),
						'template' => ''
					))
				)
			),
			array(
				/**
				* @link https://www.vbulletin.com/forum/misc.php?do=bbcode#quote
				*/
				'[quote={PARSE=/(?<author>.+?)(?:;(?<id>\\d+))?/} author={TEXT1;optional} id={UINT;optional}]{TEXT2}[/quote]',
				'',
				array(
					'bbcodeName' => 'QUOTE',
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
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo={PARSE=/bar/},{PARSE=/baz/}/]',
				'',
				new RuntimeException("{PARSE} tokens can only be used has the sole content of an attribute")
			),
			array(
				// Here, we don't know to which attribute the token {INT} in attribute c correponds
				'[foo a={INT} b={INT} c={INT},{NUMBER} /]',
				'',
				new RuntimeException("Token {INT} used in attribute 'c' is ambiguous")
			),
			array(
				'[foo={NUMBER},{NUMBER} /]',
				'',
				new RuntimeException("Token {NUMBER} used multiple times in attribute foo's definition")
			),
			array(
				'[foo={PARSE=/(?<bar>\\d+)/} foo={PARSE=/(?<bar>\\D+)/}]',
				'',
				new RuntimeException("Ambiguous attribute 'bar' created using different regexps needs to be explicitly defined")
			),
			array(
				'[foo={PARSE}]',
				'',
				new RuntimeException("Malformed token 'PARSE'")
			),
			array(
				'[foo={RANGE1}]',
				'',
				new RuntimeException("Malformed token 'RANGE1'")
			),
			array(
				'[foo]{NUMBER1},{NUMBER2}[/foo]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('content'),
						'defaultAttribute'  => 'content'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('content', '/^(?<content0>\\d+),(?<content1>\\d+)$/D')
						),
						'attributes' => array(
							'content0' => array(
								'filterChain' => array(new Number)
							),
							'content1' => array(
								'filterChain' => array(new Number)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo]{NUMBER1} * {NUMBER2}[/foo]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode' => new BBCode(array(
						'contentAttributes' => array('content'),
						'defaultAttribute'  => 'content'
					)),
					'tag'    => new Tag(array(
						'attributePreprocessors' => array(
							array('content', '/^(?<content0>\\d+) \\* (?<content1>\\d+)$/D')
						),
						'attributes' => array(
							'content0' => array(
								'filterChain' => array(new Number)
							),
							'content1' => array(
								'filterChain' => array(new Number)
							)
						),
						'template' => ''
					))
				)
			),
			array(
				'[foo $tagName=bar]',
				'',
				array(
					'bbcodeName' => 'FOO',
					'bbcode'     => new BBCode(array('tagName' => 'BAR')),
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[B $forceLookahead=false]{TEXT}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('forceLookahead' => false)),
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[B $forceLookahead=true]{TEXT}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('forceLookahead' => true)),
					'tag'        => new Tag(array('template' => ''))
				)
			),
			array(
				'[B #autoReopen=false]{TEXT}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'rules'    => array(
							'autoReopen' => false,
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						),
						'template' => ''
					))
				)
			),
			array(
				'[B #autoReopen=true]{TEXT}[/B]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'rules'    => array(
							'autoReopen' => true,
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						),
						'template' => ''
					))
				)
			),
			array(
				'[X #closeParent=X #closeParent=Y]',
				'',
				array(
					'bbcodeName' => 'X',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'rules'    => array(
							'closeParent' => array('X', 'Y'),
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						),
						'template' => ''
					))
				)
			),
			array(
				'[X #closeParent=X,Y]',
				'',
				array(
					'bbcodeName' => 'X',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'rules'    => array(
							'closeParent' => array('X', 'Y'),
							'defaultChildRule' => 'allow',
							'defaultDescendantRule' => 'allow'
						),
						'template' => ''
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'<b>{L_FOO}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'template' => '<b><xsl:value-of select="$L_FOO"/></b>'
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'<b>{TEXT2}</b>',
				new RuntimeException('Token {TEXT2} is ambiguous or undefined')
			),
			array(
				'[b]{TEXT}[/b]',
				'<b>{NUMBER1}</b>',
				new RuntimeException('Token {NUMBER1} is ambiguous or undefined')
			),
			array(
				'[x]{NUMBER}[/x]',
				'<span title="{TEXT}"/>',
				new RuntimeException('Token {TEXT} is ambiguous or undefined')
			),
			array(
				'[b]{TEXT}[/b]',
				'<span title="{NUMBER}"/>',
				new RuntimeException('Token {NUMBER} is ambiguous or undefined')
			),
			array(
				'[b]{TEXT}[/b]',
				'<span title="{L_FOO}">...</span>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'template' => '<span title="{$L_FOO}">...</span>'
					))
				)
			),
			array(
				'[url={URL}]{TEXT}[/url]',
				'<a href="{URL}">{TEXT}</a>',
				array(
					'bbcodeName' => 'URL',
					'bbcode'     => new BBCode(array(
						'defaultAttribute' => 'url'
					)),
					'tag'        => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							)
						),
						'template'   => '<a href="{@url}"><xsl:apply-templates/></a>'
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'<b title="{TEXT}">{TEXT}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode	,
					'tag'        => new Tag(array(
						'template' => '<b title="{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}"><xsl:apply-templates/></b>'
					))
				)
			),
			array(
				'[b id={IDENTIFIER}]{TEXT}[/b]',
				'<span title="{IDENTIFIER}{IDENTIFIER}"/>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('defaultAttribute' => 'id')),
					'tag'        => new Tag(array(
						'attributes' => array(
							'id' => array(
								'filterChain' => array(new Identifier)
							)
						),
						'template'   => '<span title="{@id}{@id}"/>'
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'foo',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'template' => 'foo'
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'foo{TEXT}bar',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'template' => 'foo<xsl:apply-templates/>bar'
					))
				)
			),
			array(
				'[b url={URL}]{TEXT}[/b]',
				'<hr><img src={URL}><br>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('defaultAttribute' => 'url')),
					'tag'        => new Tag(array(
						'attributes' => array(
							'url' => array(
								'filterChain' => array(new Url)
							)
						),
						'template' => '<hr/><img src="{@url}"/><br/>'
					))
				)
			),
			array(
				'[b]{TEXT}[/b]',
				'',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array(
						'template' => ''
					))
				)
			),
			array(
				'[b username={TEXT}]',
				'Hello {TEXT}',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('defaultAttribute' => 'username')),
					'tag'        => new Tag(array(
						'attributes' => array(
							'username' => array()
						),
						'template' => 'Hello <xsl:value-of select="@username"/>'
					))
				)
			),
			array(
				'[b foo={TEXT1} bar={TEXT2}]',
				'<div>{TEXT1} {TEXT2}</div>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('defaultAttribute' => 'foo')),
					'tag'        => new Tag(array(
						'attributes' => array(
							'foo' => array(),
							'bar' => array()
						),
						'template' => '<div><xsl:value-of select="@foo"/> <xsl:value-of select="@bar"/></div>'
					))
				)
			),
			array(
				'[b foo={TEXT}]',
				'<b>{@foo}</div>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode(array('defaultAttribute' => 'foo')),
					'tag'        => new Tag(array(
						'attributes' => array(
							'foo' => array()
						),
						'template' => '<b><xsl:value-of select="@foo"/></b>'
					))
				)
			),
			array(
				'[B]
					{TEXT}
				[/B]',
				'<b>{TEXT}</b>',
				array(
					'bbcodeName' => 'B',
					'bbcode'     => new BBCode,
					'tag'        => new Tag(array('template' => '<b><xsl:apply-templates/></b>'))
				)
			),
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