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
	public function testReplaceCallbacks($original, $expected, $functions)
	{
		$generator = new CallbackGenerator;
		$this->assertEquals($expected, $generator->replaceCallbacks($original));
		$this->assertEquals($functions, $generator->getFunctions());
	}

	public function getReplaceCallbacksTests()
	{
		return [
			[
				[],
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
								new Code('c0FD361FA')
							]
						]
					]
				],
				[
					'c0FD361FA' => "/**\n* @param {!Tag} tag\n* @param {!Object} tagConfig\n*/\nfunction c0FD361FA(tag,tagConfig){return executeAttributePreprocessors(tag,tagConfig);}"
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
										new Code('c5E0742FA')
									]
								]
							]
						]
					]
				],
				[
					'c5E0742FA' => "/**\n* @param {*} attrValue\n* @param {!string} attrName\n*/\nfunction c5E0742FA(attrValue,attrName){return (function(z){return z})(\"FOO\");}"
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
								new Code('c1FD39C63')
							]
						]
					]
				],
				[
					'c1FD39C63' => "/**\n* @param {!Tag} tag\n* @param {!Object} tagConfig\n*/\nfunction c1FD39C63(tag,tagConfig){return (function(v){return v})(registeredVars[\"registered\"]);}"
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
				],
				[]
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
				],
				[]
			],
		];
	}
}