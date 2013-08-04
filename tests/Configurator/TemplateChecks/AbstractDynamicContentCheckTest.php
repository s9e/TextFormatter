<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
*/
class AbstractDynamicContentCheckTest extends Test
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
	* @testdox Stylesheet parameters are considered safe
	*/
	public function testStylesheetParameter()
	{
		$node = $this->loadTemplate('<b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to an unknown attribute are unsafe
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testVarUnknown()
	{
		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to a safe attribute are safe
	*/
	public function testVarSafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Variables pointing to an unsafe attribute are unsafe
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testVarUnsafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 0;

		try
		{
			$check = new DummyContentCheck;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->lastChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Variables pointing to a safe variable are safe
	*/
	public function testVarVarSafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="foo" select="@foo"/><xsl:variable name="var" select="$foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Variables pointing to an unsafe variable are unsafe
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testVarVarUnsafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="foo" select="@foo"/><xsl:variable name="var" select="$foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 0;

		try
		{
			$check = new DummyContentCheck;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->lastChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Variables pointing to a stylesheet parameter are safe
	*/
	public function testVarParamSafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="foo" select="$FOO"/><xsl:variable name="var" select="$foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to a stylesheet parameter of the same name are safe
	*/
	public function testVarParamSameName()
	{
		$node = $this->loadTemplate('<xsl:variable name="FOO" select="$FOO"/><b><xsl:value-of select="$FOO"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Local parameters pointing to an unknown attribute are unsafe
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testParamUnknown()
	{
		$node = $this->loadTemplate('<xsl:param name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Local parameters pointing to a safe attribute are safe
	*/
	public function testParamSafe()
	{
		$node = $this->loadTemplate('<xsl:param name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}
}

class DummyContentCheck extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMNode $template)
	{
		return $template->getElementsByTagName('b');
	}

	protected function isSafe(Attribute $attribute)
	{
		// Use the attribute's default value as a flag
		return $attribute->defaultValue;
	}
}