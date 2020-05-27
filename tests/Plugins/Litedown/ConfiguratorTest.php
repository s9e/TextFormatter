<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Plugins\Litedown\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Turns on automatic paragraph management
	*/
	public function testManageParagraphs()
	{
		$this->configurator->plugins->load('Litedown');
		$this->assertTrue($this->configurator->rulesGenerator->contains('ManageParagraphs'));
	}

	/**
	* @testdox Automatically creates a "URL" tag
	*/
	public function testCreatesURL()
	{
		$this->configurator->plugins->load('Litedown');
		$this->assertTrue($this->configurator->tags->exists('URL'));
	}

	/**
	* @testdox Does not overwrite the "URL" tag if it already exists
	*/
	public function testPreservesURL()
	{
		$tag       = $this->configurator->tags->add('URL');
		$tagConfig = $tag->asConfig();

		$this->configurator->plugins->load('Litedown');

		$this->assertSame($tag, $this->configurator->tags->get('URL'));
		$this->assertEquals($tagConfig, $tag->asConfig());
	}

	/**
	* @testdox addHeadersId() create a slug attribute for H1 to H6
	*/
	public function testAddHeadersIdSlug()
	{
		$this->configurator->Litedown->addHeadersId();

		$tags = $this->configurator->tags;
		$this->assertTrue($tags['H1']->attributes->exists('slug'));
		$this->assertTrue($tags['H2']->attributes->exists('slug'));
		$this->assertTrue($tags['H3']->attributes->exists('slug'));
		$this->assertTrue($tags['H4']->attributes->exists('slug'));
		$this->assertTrue($tags['H5']->attributes->exists('slug'));
		$this->assertTrue($tags['H6']->attributes->exists('slug'));
	}

	/**
	* @testdox addHeadersId() adds a conditional "id" attribute to H1 to H6 templates
	*/
	public function testAddHeadersIdTemplate()
	{
		$this->configurator->Litedown->addHeadersId();

		$xsl = '<xsl:if test="@slug"><xsl:attribute name="id"><xsl:value-of select="@slug"/></xsl:attribute></xsl:if>';

		$tags = $this->configurator->tags;
		$this->assertStringContainsString($xsl, $tags['H1']->template);
		$this->assertStringContainsString($xsl, $tags['H2']->template);
		$this->assertStringContainsString($xsl, $tags['H3']->template);
		$this->assertStringContainsString($xsl, $tags['H4']->template);
		$this->assertStringContainsString($xsl, $tags['H5']->template);
		$this->assertStringContainsString($xsl, $tags['H6']->template);
	}

	/**
	* @testdox addHeadersId('foo-') adds a "foo-" prefix to the id attribute
	*/
	public function testAddHeadersIdTemplatePrefix()
	{
		$this->configurator->Litedown->addHeadersId('foo-');

		$xsl = '<xsl:if test="@slug"><xsl:attribute name="id">foo-<xsl:value-of select="@slug"/></xsl:attribute></xsl:if>';

		$tags = $this->configurator->tags;
		$this->assertStringContainsString($xsl, $tags['H1']->template);
		$this->assertStringContainsString($xsl, $tags['H2']->template);
		$this->assertStringContainsString($xsl, $tags['H3']->template);
		$this->assertStringContainsString($xsl, $tags['H4']->template);
		$this->assertStringContainsString($xsl, $tags['H5']->template);
		$this->assertStringContainsString($xsl, $tags['H6']->template);
	}

	/**
	* @testdox addHeadersId() can be called multiple times
	*/
	public function testAddHeadersIdTwice()
	{
		$this->configurator->Litedown->addHeadersId('');
		$this->assertEquals(
			'<h1><xsl:if test="@slug"><xsl:attribute name="id"><xsl:value-of select="@slug"/></xsl:attribute></xsl:if><xsl:apply-templates/></h1>',
			(string) $this->configurator->tags['H1']->template
		);

		$this->configurator->Litedown->addHeadersId('foo-');
		$this->assertEquals(
			'<h1><xsl:if test="@slug"><xsl:attribute name="id">foo-<xsl:value-of select="@slug"/></xsl:attribute></xsl:if><xsl:apply-templates/></h1>',
			(string) $this->configurator->tags['H1']->template
		);
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$this->assertIsArray($this->configurator->Litedown->asConfig());
	}

	/**
	* @testdox asConfig() returns decodeHtmlEntities is saved as a boolean
	*/
	public function testAsConfigDecodeHtmlEntities()
	{
		$this->configurator->Litedown->decodeHtmlEntities = 1;
		$this->assertArrayMatches(
			['decodeHtmlEntities' => true],
			$this->configurator->Litedown->asConfig()
		);
	}

	/**
	* @testdox getJSHints() returns ['LITEDOWN_DECODE_HTML_ENTITIES' => 0] by default
	*/
	public function testGetJSHintsFalse()
	{
		$plugin = $this->configurator->Litedown;
		$this->assertSame(
			['LITEDOWN_DECODE_HTML_ENTITIES' => 0],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['LITEDOWN_DECODE_HTML_ENTITIES' => 1] if decodeHtmlEntities is true
	*/
	public function testGetJSHintsTrue()
	{
		$plugin = $this->configurator->plugins->load('Litedown', ['decodeHtmlEntities' => true]);
		$this->assertSame(
			['LITEDOWN_DECODE_HTML_ENTITIES' => 1],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSParser() returns a parser
	*/
	public function testGetJSParser()
	{
		$this->assertGreaterThan(1000, strlen($this->configurator->Litedown->getJSParser()));
	}
}