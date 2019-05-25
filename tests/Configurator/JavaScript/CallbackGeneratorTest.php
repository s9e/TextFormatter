<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\CallbackGenerator;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript\CallbackGenerator
*/
class CallbackGeneratorTest extends Test
{
	/**
	* @testdox replaceCallbacks() tests
	* @dataProvider getReplaceCallbacksTests
	*/
	public function testReplaceCallbacks($original, $expected)
	{
		$generator = new CallbackGenerator;
		$this->assertEquals($expected, $generator->replaceCallbacks($original));
	}

	public function getReplaceCallbacksTests()
	{
		return [
			[
				[],
				[]
			],
			[
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								[
									'js' => 'executeAttributePreprocessors',
									'params' => [
										'tag' => null,
										'tagConfig' => null
									]
								]
							]
						]
					]
				],
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								new Code("/**\n* @param {!Tag} tag\n* @param {!Object} tagConfig\n*/\nfunction(tag,tagConfig){return executeAttributePreprocessors(tag,tagConfig);}")
							]
						]
					]
				]
			],
			[
				[
					'tags' => [
						'X' => [
							'attributes' => [
								'x' => [
									'filterChain' => [
										[
											'js' => 'function(z){return z}',
											'params' => ['FOO']
										]
									]
								]
							]
						]
					]
				],
				[
					'tags' => [
						'X' => [
							'attributes' => [
								'x' => [
									'filterChain' => [
										new Code("/**\n* @param {*} attrValue\n* @param {!string} attrName\n*/\nfunction(attrValue,attrName){return (function(z){return z})(\"FOO\");}")
									]
								]
							]
						]
					]
				]
			],
			[
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								[
									'js' => 'function(v){return v}',
									'params' => [
										'registered' => null
									]
								]
							]
						]
					]
				],
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								new Code("/**\n* @param {!Tag} tag\n* @param {!Object} tagConfig\n*/\nfunction(tag,tagConfig){return (function(v){return v})(registeredVars[\"registered\"]);}")
							]
						]
					]
				]
			],
			[
				[
					'tags' => [
						'X' => ['filterChain' => [['js' => 'returnFalse']]]
					]
				],
				[
					'tags' => [
						'X' => ['filterChain' => [new Code('returnFalse')]]
					]
				]
			],
			[
				[
					'tags' => [
						'X' => ['filterChain' => [['js' => 'returnTrue']]]
					]
				],
				[
					'tags' => [
						'X' => ['filterChain' => [new Code('returnTrue')]]
					]
				]
			],
			[
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								[
									'js' => 'function(innerText,outerText,tagText){}',
									'params' => [
										'innerText' => null,
										'outerText' => null,
										'tagText'   => null,
									]
								]
							]
						]
					]
				],
				[
					'tags' => [
						'X' => [
							'filterChain' => [
								new Code("/**\n* @param {!Tag} tag\n* @param {!Object} tagConfig\n*/\nfunction(tag,tagConfig){return (function(innerText,outerText,tagText){})((tag.getEndTag() ? text.substr(tag.getPos() + tag.getLen(), tag.getEndTag().getPos() - tag.getPos() - tag.getLen()) : \"\"),text.substr(tag.getPos(), (tag.getEndTag() ? tag.getEndTag().getPos() + tag.getEndTag().getLen() - tag.getPos() : tag.getLen())),text.substr(tag.getPos(), tag.getLen()));}")
							]
						]
					]
				]
			],
		];
	}
}