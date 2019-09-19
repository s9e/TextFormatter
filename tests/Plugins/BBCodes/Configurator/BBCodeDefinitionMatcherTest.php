<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\FilterSyntaxMatcher;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeDefinitionMatcher;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeDefinitionMatcher
*/
class BBCodeDefinitionMatcherTest extends Test
{
	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($filterString, $expected)
	{
		if ($expected === false)
		{
			$this->expectException('RuntimeException');
			$this->expectExceptionMessage('Cannot parse');
		}

		$parser = new RecursiveParser;
		$parser->setMatchers([
			new BBCodeDefinitionMatcher($parser),
			new FilterSyntaxMatcher($parser)
		]);

		$this->assertEquals($expected, $parser->parse($filterString, 'BBCodeDefinition')['value']);
	}

	public function getParseTests()
	{
		return [
			[
				'[b]{TEXT}[/b]',
				[
					'bbcodeName' => 'b',
					'content'    => [['id' => 'TEXT']]
				]
			],
			[
				'[br]',
				[
					'bbcodeName' => 'br',
					'content'    => []
				]
			],
			[
				'[X foo={TEXT}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT']]
						]
					]
				]
			],
			[
				'[X foo="{TEXT}"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT']]
						]
					]
				]
			],
			[
				'[X foo="{NUMBER1}{NUMBER2}"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								['id' => 'NUMBER1'],
								['id' => 'NUMBER2']
							]
						]
					]
				]
			],
			[
				'[X foo="{NUMBER1} {NUMBER2}"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								['id' => 'NUMBER1'],
								' ',
								['id' => 'NUMBER2']
							]
						]
					]
				]
			],
			[
				"[X foo='{NUMBER1} {NUMBER2}']",
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								['id' => 'NUMBER1'],
								' ',
								['id' => 'NUMBER2']
							]
						]
					]
				]
			],
			[
				"[X foo={NUMBER1} {NUMBER2}]",
				false
			],
			[
				'[X foo={NUMBER1},{NUMBER2}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								['id' => 'NUMBER1'],
								',',
								['id' => 'NUMBER2']
							]
						]
					]
				]
			],
			[
				'[X]{NUMBER1},{NUMBER2}[/x]',
				[
					'bbcodeName' => 'X',
					'content'    => [
						['id' => 'NUMBER1'],
						',',
						['id' => 'NUMBER2']
					]
				]
			],
			[
				'[X] {NUMBER1} {NUMBER2} [/x]',
				[
					'bbcodeName' => 'X',
					'content'    => [
						['id' => 'NUMBER1'],
						' ',
						['id' => 'NUMBER2']
					]
				]
			],
			[
				'[X foo={TEXT1} bar={TEXT2}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT1']]
						],
						[
							'name'    => 'bar',
							'content' => [['id' => 'TEXT2']]
						]
					]
				]
			],
			[
				'[X $forceLookahead=true]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'forceLookahead', 'value' => true]]
				]
			],
			[
				'[X $forceLookahead]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'forceLookahead']]
				]
			],
			[
				'[X $foo=[1,2]]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'foo', 'value' => [1, 2]]]
				]
			],
			[
				'[X $foo="]"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'foo', 'value' => ']']]
				]
			],
			[
				'[X #autoClose=false]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose', 'value' => false]]
				]
			],
			[
				'[X #autoClose=True]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose', 'value' => true]]
				]
			],
			[
				'[X #autoClose]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose']]
				]
			],
			[
				'[X #closeParent=foo,bar]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'    => [
						['name' => 'closeParent', 'value' => 'foo'],
						['name' => 'closeParent', 'value' => 'bar']
					]
				]
			],
			[
				'[X foo={TEXT?}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								[
									'id'      => 'TEXT',
									'options' => [['name' => 'required', 'value' => false]]
								]
							]
						]
					]
				]
			],
			[
				'[X foo={REGEXP=/foo/i}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'foo',
						'content' => [[
							'id'          => 'REGEXP',
							'filterValue' => new Regexp('/foo/i', true)
						]]
					]]
				]
			],
			[
				'[X foo={TEXT1}
					bar={TEXT2}
				]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT1']]
						],
						[
							'name'    => 'bar',
							'content' => [['id' => 'TEXT2']]
						]
					]
				]
			],
			[
				'[X foo={
						TEXT1;
						foo=1;
						bar=["ab", "cd"];
					}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'foo',
						'content' => [[
							'id'      => 'TEXT1',
							'options' => [
								['name' => 'foo', 'value' => 1           ],
								['name' => 'bar', 'value' => ['ab', 'cd']]
							]
						]]
					]]
				]
			],
			[
				'[X $tagName=FOO
					$filterChain.append=MyFilter::foo($tag, 1, 2)
					$filterChain.append=MyFilter::bar()
					$filterChain.prepend=MyFilter::baz]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [
						['name' => 'tagName',             'value' => 'FOO'                      ],
						['name' => 'filterChain.append',  'value' => 'MyFilter::foo($tag, 1, 2)'],
						['name' => 'filterChain.append',  'value' => 'MyFilter::bar()'          ],
						['name' => 'filterChain.prepend', 'value' => 'MyFilter::baz'            ]
					]
				]
			],
			[
				'[URL={URL;useContent}]{TEXT}[/url]',
				[
					'bbcodeName' => 'URL',
					'content'    => [['id' => 'TEXT']],
					'attributes' => [[
						'name'    => 'URL',
						'content' => [[
							'id' => 'URL',
							'options' => [['name' => 'useContent']]
						]]
					]]
				]
			],
			[
				'[X x={TEXT;preFilter=#int}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [
								['name' => 'filterChain.prepend', 'value' => '#int']
							]
						]]
					]]
				]
			],
			[
				'[X x={TEXT;postFilter=strtolower,ucwords}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [
								['name'  => 'filterChain.append', 'value' => 'strtolower'],
								['name'  => 'filterChain.append', 'value' => 'ucwords'   ]
							]
						]]
					]]
				]
			],
			[
				'[X x={TEXT;filterChain.prepend=str_replace($attrValue, "_", "-")}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [[
								'name'  => 'filterChain.prepend',
								'value' => 'str_replace($attrValue, "_", "-")'
							]]
						]]
					]]
				]
			],
			[
				'[X x=\'{TEXT;filterChain.prepend=str_replace($attrValue, "_", "-")}\']',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [[
								'name'  => 'filterChain.prepend',
								'value' => 'str_replace($attrValue, "_", "-")'
							]]
						]]
					]]
				]
			],
			[
				'[LIST type={HASHMAP=a:lower-alpha,1:decimal,I:upper-roman}]',
				[
					'bbcodeName' => 'LIST',
					'content'    => [],
					'attributes' => [[
						'name'    => 'type',
						'content' => [[
							'id'          => 'HASHMAP',
							'filterValue' => 'a:lower-alpha,1:decimal,I:upper-roman'
						]]
					]]
				]
			],
			[
				// Leading digits are interpreted as a literal and the regexp won't backtrack
				'[LIST type={HASHMAP=1:decimal,a:lower-alpha,I:upper-roman}]',
				[
					'bbcodeName' => 'LIST',
					'content'    => [],
					'attributes' => [[
						'name'    => 'type',
						'content' => [[
							'id'          => 'HASHMAP',
							'filterValue' => '1:decimal,a:lower-alpha,I:upper-roman'
						]]
					]]
				]
			],
		];
	}
}