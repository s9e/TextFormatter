<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder
*/
class TemplateBuilderTest extends Test
{
	/**
	* @testdox getTemplate() returns an empty string by default
	*/
	public function testEmpty()
	{
		$templateBuilder = new TemplateBuilder;
		$this->assertEmpty($templateBuilder->getTemplate([]));
	}

	/**
	* @testdox Supports Flash objects
	*/
	public function testFlash()
	{
		$templateBuilder = new TemplateBuilder;
		$this->assertContains('<object', $templateBuilder->getTemplate(['flash' => ['src' => '']]));
	}

	/**
	* @testdox Supports iframes
	*/
	public function testIframe()
	{
		$templateBuilder = new TemplateBuilder;
		$this->assertContains('<iframe', $templateBuilder->getTemplate(['iframe' => ['src' => '']]));
	}

	/**
	* @testdox Supports multiple choice templates
	*/
	public function testMulti()
	{
		$templateBuilder = new TemplateBuilder;
		$template        = $templateBuilder->getTemplate([
			'choose' => [
				'when'      => [
					'test'   => '@foo',
					'iframe' => ['width' => '100', 'height' => '100', 'src' => 'foo']
				],
				'otherwise' => [
					'flash' => ['width' => '200', 'height' => '200', 'src' => 'bar']
				]
			]
		]);
		$this->assertContains('<iframe', $template);
		$this->assertContains('<object', $template);
	}

	/**
	* @testdox build() adds the data-s9e-mediaembed attribute on the wrapper if applicable
	*/
	public function testSiteId()
	{
		$templateBuilder = new TemplateBuilder;
		$this->assertContains(
			'<span data-s9e-mediaembed="foo"',
			$templateBuilder->build('foo', ['iframe' => ['src' => '']])
		);
	}

	/**
	* @testdox build() does not add the data-s9e-mediaembed attribute on XSL elements
	*/
	public function testSiteIdChoose()
	{
		$attributes = [
			'choose' => [
				'when'      => [
					'test'   => '@foo',
					'iframe' => ['width' => '100', 'height' => '100', 'src' => 'foo']
				],
				'otherwise' => [
					'flash' => ['width' => '200', 'height' => '200', 'src' => 'bar']
				]
			]
		];

		$templateBuilder = new TemplateBuilder;
		$template        = $templateBuilder->build('foo', $attributes);

		$this->assertContains('<span data-s9e-mediaembed="foo"', $template);
		$this->assertNotRegexp('(<xsl:[^>]+data-s9e-mediaembed)', $template);
	}
}