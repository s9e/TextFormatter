<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Color;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicCSS;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicCSS
*/
class DisallowUnsafeDynamicCSSTest extends Test
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
	* @testdox Allowed: <style>.important { color:red }</style>
	*/
	public function testAllowedStaticElement()
	{
		$node = $this->loadTemplate('<style>.important { color:red }</style>');

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b style="color:red">...</b>
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<b style="color:red">...</b>');

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #color: <b style="color:{@foo}">...</b>
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<b style="color:{@foo}">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Color);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b style="color:{@foo}">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknown()
	{
		$node = $this->loadTemplate('<b style="color:{@foo}">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('style'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b style="color:{@foo}">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfiltered()
	{
		$node = $this->loadTemplate('<b style="color:{@foo}">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('style'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b style="color:{.}">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedDot()
	{
		$node = $this->loadTemplate('<b style="color:{.}">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('style'));

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #color: <b><xsl:copy-of select="@style"/>...</b>
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@style"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('style')->filterChain->append(new Color);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:copy-of select="@style"/>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'style'
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@style"/>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:copy-of select="@style"/>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'style' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@style"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('style');

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #color: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Color);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknownValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="style"><xsl:value-of select="."/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedValueOfDot()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="."/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:attribute name="style"><xsl:apply-templates/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot allow unfiltered data in this context
	*/
	public function testDisallowedApplyTemplates()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:apply-templates/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:attribute name="style"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess context due to 'xsl:for-each'
	*/
	public function testUnsafeContext()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}
}