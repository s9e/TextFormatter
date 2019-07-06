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
		if ($expected instanceof RuntimeException)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
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
					'bbcodeName' => 'B',
					'content'    => [['id' => 'TEXT']]
				]
			],
			[
				'[br]',
				[
					'bbcodeName' => 'BR',
					'content'    => []
				]
			],
			[
				'[x foo={TEXT}]',
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
				'[x foo={TEXT1} bar={TEXT2}]',
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
				'[x $forceLookahead=true]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [
						[
							'name'  => 'forceLookahead',
							'value' => true
						]
					]
				]
			],
			[
				'[x $forceLookahead]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'forceLookahead']]
				]
			],
			[
				'[x $foo=[1,2]]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [
						[
							'name'  => 'foo',
							'value' => [1, 2]
						]
					]
				]
			],
			[
				'[x $foo="]"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [
						[
							'name'  => 'foo',
							'value' => ']'
						]
					]
				]
			],
			[
				'[x #autoClose=false]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'    => [
						[
							'name'  => 'autoClose',
							'value' => false
						]
					]
				]
			],
			[
				'[x #autoClose]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose']]
				]
			],
			[
				'[x #closeParent=foo,bar]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'    => [
						[
							'name'  => 'closeParent',
							'value' => 'foo'
						],
						[
							'name'  => 'closeParent',
							'value' => 'bar'
						]
					]
				]
			],
		];
	}
}