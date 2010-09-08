<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class CensorTest extends \PHPUnit_Framework_TestCase
{
	public function testParserBasicCensor()
	{
		$text     = 'You dirty apple';
		$expected = '<rt>You dirty <C>apple</C></rt>';
		$actual   = $this->cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testParserCensorWithReplacement()
	{
		$text     = 'You dirty banana';
		$expected = '<rt>You dirty <C with="pear">banana</C></rt>';
		$actual   = $this->cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testParserBasicCensor
	*/
	public function testRendererBasicCensorWithDefaultReplacement()
	{
		$text     = 'You dirty apple';
		$expected = 'You dirty ****';

		$parsed   = $this->cb->getParser()->parse($text);
		$actual   = $this->cb->getRenderer()->render($this->cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testParserCensorWithReplacement
	*/
	public function testRendererBasicCensorWithCustomReplacement()
	{
		$this->cb->setCensorOption('default_replacement', '@$#!');

		$text     = 'You dirty apple';
		$expected = 'You dirty @$#!';

		$parsed   = $this->cb->getParser()->parse($text);
		$actual   = $this->cb->getRenderer()->render($this->cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testParserBasicCensor
	*/
	public function testRendererCensorWithReplacement()
	{
		$text     = 'You dirty banana';
		$expected = 'You dirty pear';

		$parsed   = $this->cb->getParser()->parse($text);
		$actual   = $this->cb->getRenderer()->render($parsed);

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$this->cb = new ConfigBuilder;
		$this->cb->addCensor('apple');
		$this->cb->addCensor('banana', 'pear');
	}
}