<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowPHPTags;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowPHPTags
*/
class DisallowPHPTagsTest extends Test
{
	protected function loadTemplate($template)
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom->documentElement;
	}

	/**
	* @testdox Disallowed: <b><?php ?></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the template
	*/
	public function testTemplate()
	{
		$node = $this->loadTemplate('<b><?php ?></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><?PHP ?></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the template
	*/
	public function testTemplateCase()
	{
		$node = $this->loadTemplate('<b><?PHP ?></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="php"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the output
	*/
	public function testOutput()
	{
		$node = $this->loadTemplate('<b><xsl:processing-instruction name="php"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="PHP"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the output
	*/
	public function testOutputCase()
	{
		$node = $this->loadTemplate('<b><xsl:processing-instruction name="PHP"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="{@foo}"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Dynamic processing instructions are not allowed
	*/
	public function testDynamic()
	{
		$node = $this->loadTemplate('<b><xsl:processing-instruction name="{@foo}"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b><?foo ?></b>
	*/
	public function testAllowedTemplate()
	{
		$node = $this->loadTemplate('<b><?foo ?></b>');
		$check = new DisallowPHPTags;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:processing-instruction name="foo"/></b>
	*/
	public function testAllowedOutput()
	{
		$node = $this->loadTemplate('<b><xsl:processing-instruction name="foo"/></b>');
		$check = new DisallowPHPTags;
		$check->check($node, new Tag);
	}
}