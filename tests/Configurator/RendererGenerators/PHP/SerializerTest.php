<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use DOMDocument;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer
*/
class SerializerTest extends Test
{
	/**
	* @testdox serialize() ignores text nodes in source tree
	*/
	public function testIgnoresTextNodes()
	{
		$xml = '<template>
					<element name="br" id="1" void="yes">
						<closeTag id="1"/>
					</element>
				</template>';

		$expected = "\$this->out.='<'.htmlspecialchars('br',3);\$this->out.='>';";

		$serializer = new Serializer;
		$ir = new DOMDocument;
		$ir->loadXML($xml);

		$this->assertSame($expected, $serializer->serialize($ir->documentElement));
	}

	/**
	* @testdox serialize() tests
	* @dataProvider getSerializeTests
	*/
	public function testSerialize($xml, $expected, $setup = null)
	{
		$ir = new DOMDocument;
		$ir->preserveWhiteSpace = false;
		$ir->loadXML($xml);

		$serializer = new Serializer;
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}
		if (isset($setup))
		{
			$setup($serializer);
		}

		$this->assertSame($expected, $serializer->serialize($ir->documentElement));
	}

	public function getSerializeTests()
	{
		return [
			[
				'<template>
					<switch branch-key="@foo">
						<case branch-values=\'a:1:{i:0;s:1:"1";}\' test="@foo = 1">
							<output escape="text" type="literal">1</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"2";}\' test="@foo = 2">
							<output escape="text" type="literal">2</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"3";}\' test="@foo = 3">
							<output escape="text" type="literal">3</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"4";}\' test="4 = @foo">
							<output escape="text" type="literal">4</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"5";}\' test="5 = @foo">
							<output escape="text" type="literal">5</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"6";}\' test="@foo = 6">
							<output escape="text" type="literal">6</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"7";}\' test="@foo = 7">
							<output escape="text" type="literal">7</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"8";}\' test="@foo = 8">
							<output escape="text" type="literal">8</output>
						</case>
						<case>
							<output escape="text" type="literal">default</output>
						</case>
					</switch>
				</template>',
				"switch(\$node->getAttribute('foo')){case'1':\$this->out.='1';break;case'2':\$this->out.='2';break;case'3':\$this->out.='3';break;case'4':\$this->out.='4';break;case'5':\$this->out.='5';break;case'6':\$this->out.='6';break;case'7':\$this->out.='7';break;case'8':\$this->out.='8';break;default:\$this->out.='default';}"
			],
			[
				'<template><hash/></template>',
				new RuntimeException
			],
			[
				'<template>
					<switch branch-key="@foo">
						<case branch-values=\'a:1:{i:0;s:1:"1";}\' test="@foo = 1">
							<output escape="text" type="literal">1</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"2";}\' test="@foo = 2">
							<output escape="text" type="literal">2</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"3";}\' test="@foo = 3">
							<output escape="text" type="literal">3</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"4";}\' test="4 = @foo">
							<output escape="text" type="literal">4</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"5";}\' test="5 = @foo">
							<output escape="text" type="literal">5</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"6";}\' test="@foo = 6">
							<output escape="text" type="literal">6</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"7";}\' test="@foo = 7">
							<output escape="text" type="literal">7</output>
						</case>
						<case branch-values=\'a:2:{i:0;s:1:"8";i:1;s:2:"44";}\' test="@foo = 8 or @foo = 44">
							<switch branch-key="@bar">
								<case branch-values=\'a:1:{i:0;s:1:"1";}\' test="@bar = 1">
									<output escape="text" type="literal">1</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"2";}\' test="@bar = 2">
									<output escape="text" type="literal">2</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"3";}\' test="@bar = 3">
									<output escape="text" type="literal">3</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"4";}\' test="@bar = 4">
									<output escape="text" type="literal">4</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"5";}\' test="@bar = 8">
									<output escape="text" type="literal">5</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"6";}\' test="@bar = 6">
									<output escape="text" type="literal">6</output>
								</case>
								<case branch-values=\'a:1:{i:0;s:1:"7";}\' test="@bar = 7">
									<output escape="text" type="literal">7</output>
								</case>
								<case branch-values=\'a:2:{i:0;s:1:"8";i:1;s:2:"44";}\' test="@bar = 8 or @bar = 44">
									<output escape="text" type="literal">8</output>
								</case>
							</switch>
						</case>
					</switch>
				</template>',
				"switch(\$node->getAttribute('foo')){case'1':\$this->out.='1';break;case'2':\$this->out.='2';break;case'3':\$this->out.='3';break;case'4':\$this->out.='4';break;case'5':\$this->out.='5';break;case'6':\$this->out.='6';break;case'7':\$this->out.='7';break;case'8':case'44':switch(\$node->getAttribute('bar')){case'1':\$this->out.='1';break;case'2':\$this->out.='2';break;case'3':\$this->out.='3';break;case'4':\$this->out.='4';break;case'5':\$this->out.='5';break;case'6':\$this->out.='6';break;case'7':\$this->out.='7';break;case'8':case'44':\$this->out.='8';}}"
			],
			[
				'<template>
					<switch branch-key="@foo">
						<case branch-values=\'a:1:{i:0;s:1:"1";}\' test="@foo = 1">
							<output escape="text" type="literal">1</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:1:"2";}\' test="@foo = 2">
							<output escape="text" type="literal">2</output>
						</case>
						<case>
							<output escape="text" type="literal">default</output>
						</case>
					</switch>
				</template>',
				"switch(\$node->getAttribute('foo')){case'1':\$this->out.='1';break;case'2':\$this->out.='2';break;default:\$this->out.='default';}"
			],
			[
				'<template>
					<switch branch-key="@foo">
						<case branch-values=\'a:1:{i:0;s:3:"foo";}\' test="@foo = \'foo\'">
							<output escape="text" type="literal">foo</output>
						</case>
						<case>
							<output escape="text" type="literal">default</output>
						</case>
					</switch>
				</template>',
				"if(\$node->getAttribute('foo')==='foo'){\$this->out.='foo';}else{\$this->out.='default';}"
			],
			[
				'<template>
					<switch branch-key="@foo">
						<case branch-values=\'a:1:{i:0;s:3:"foo";}\' test="@foo = \'foo\'">
							<output escape="text" type="literal">foo</output>
						</case>
						<case branch-values=\'a:1:{i:0;s:3:"bar";}\' test="@foo = \'bar\'">
							<output escape="text" type="literal">bar</output>
						</case>
					</switch>
				</template>',
				"switch(\$node->getAttribute('foo')){case'bar':\$this->out.='bar';break;case'foo':\$this->out.='foo';}"
			],
		];
	}
}