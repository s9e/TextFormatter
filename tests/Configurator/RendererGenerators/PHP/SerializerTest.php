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
	* @testdox serialize() tests
	* @dataProvider getSerializeTests
	*/
	public function testSerialize($xml, $expected, $branchTables = array())
	{
		$ir = new DOMDocument;
		$ir->preserveWhiteSpace = false;
		$ir->loadXML($xml);

		$serializer = new Serializer;

		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, $serializer->serialize($ir->documentElement));
		$this->assertSame($branchTables, $serializer->branchTables);
	}

	public function getSerializeTests()
	{
		return array(
			array(
				'<template outputMethod="html">
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
				"if(isset(self::\$bt13027555[\$node->getAttribute('foo')])){\$n=self::\$bt13027555[\$node->getAttribute('foo')];if(\$n<4){if(\$n===0){\$this->out.='1';}elseif(\$n===1){\$this->out.='2';}elseif(\$n===2){\$this->out.='3';}else{\$this->out.='4';}}elseif(\$n===4){\$this->out.='5';}elseif(\$n===5){\$this->out.='6';}elseif(\$n===6){\$this->out.='7';}else{\$this->out.='8';}}else{\$this->out.='default';}",
				array('bt13027555' => array(1=>0,2=>1,3=>2,4=>3,5=>4,6=>5,7=>6,8=>7))
			),
			array(
				'<template><closeTag id="1"/></template>',
				new RuntimeException
			),
			array(
				'<template><hash/></template>',
				new RuntimeException
			),
			array(
				'<template outputMethod="html">
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
							<output escape="text" type="literal">8</output>
						</case>
					</switch>
				</template>',
				"if(isset(self::\$bt7794ED46[\$node->getAttribute('foo')])){\$n=self::\$bt7794ED46[\$node->getAttribute('foo')];if(\$n<4){if(\$n===0){\$this->out.='1';}elseif(\$n===1){\$this->out.='2';}elseif(\$n===2){\$this->out.='3';}else{\$this->out.='4';}}elseif(\$n===4){\$this->out.='5';}elseif(\$n===5){\$this->out.='6';}elseif(\$n===6){\$this->out.='7';}else{\$this->out.='8';}}",
				array('bt7794ED46' => array(1=>0,2=>1,3=>2,4=>3,5=>4,6=>5,7=>6,8=>7,44=>7))
			),
		);
	}
}