<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Choose;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Choose
*/
class ChooseTest extends Test
{
	/**
	* @testdox getTemplate() with one when / one otherwise
	*/
	public function testGetTemplateOneWhen()
	{
		$mock = $this->getMock(
			's9e\\TextFormatter\\Plugins\\MediaEmbed\\Configurator\\TemplateBuilder',
			['getTemplate']
		);
		$mock->expects($this->at(0))
		     ->method('getTemplate')
		     ->will($this->returnValue('foo'));
		$mock->expects($this->at(1))
		     ->method('getTemplate')
		     ->will($this->returnValue('bar'));

		$attributes = [
			'when' => [
				'test'   => '@foo',
				'iframe' => ['src' => 'foo']
			],
			'otherwise' => [
				'iframe' => ['src' => 'bar']
			]
		];
		$expected = '<xsl:choose><xsl:when test="@foo">foo</xsl:when><xsl:otherwise>bar</xsl:otherwise></xsl:choose>';

		$templateGenerator = new Choose($mock);
		$template          = $templateGenerator->getTemplate($attributes);
		$template          = $this->configurator->templateNormalizer->normalizeTemplate($template);

		$this->assertSame($expected, $template);
	}

	/**
	* @testdox getTemplate() with two when / one otherwise
	*/
	public function testGetTemplateTwoWhen()
	{
		$mock = $this->getMock(
			's9e\\TextFormatter\\Plugins\\MediaEmbed\\Configurator\\TemplateBuilder',
			['getTemplate']
		);
		$mock->expects($this->at(0))
		     ->method('getTemplate')
		     ->will($this->returnValue('foo'));
		$mock->expects($this->at(1))
		     ->method('getTemplate')
		     ->will($this->returnValue('bar'));
		$mock->expects($this->at(2))
		     ->method('getTemplate')
		     ->will($this->returnValue('baz'));

		$attributes = [
			'when' => [
				[
					'test'   => '@foo',
					'iframe' => ['src' => 'foo']
				],
				[
					'test'   => '@bar',
					'iframe' => ['src' => 'bar']
				]
			],
			'otherwise' => [
				'iframe' => ['src' => 'baz']
			]
		];
		$expected = '<xsl:choose><xsl:when test="@foo">foo</xsl:when><xsl:when test="@bar">bar</xsl:when><xsl:otherwise>baz</xsl:otherwise></xsl:choose>';

		$templateGenerator = new Choose($mock);
		$template          = $templateGenerator->getTemplate($attributes);
		$template          = $this->configurator->templateNormalizer->normalizeTemplate($template);

		$this->assertSame($expected, $template);
	}
}