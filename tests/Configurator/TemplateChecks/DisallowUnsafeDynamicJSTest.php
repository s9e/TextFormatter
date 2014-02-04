<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Number;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicJS;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicJS
*/
class DisallowUnsafeDynamicJSTest extends Test
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
	* @testdox Allowed: <script>.important { alert(1) }</script>
	*/
	public function testAllowedStaticElement()
	{
		$node = $this->loadTemplate('<script>alert(1)</script>');

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b onclick="alert(1)">...</b>
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<b onclick="alert(1)">...</b>');

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #number: <b onclick="alert({@foo})">...</b>
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<b onclick="alert({@foo})">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Number);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b onclick="alert({@foo})">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknown()
	{
		$node = $this->loadTemplate('<b onclick="alert({@foo})">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('onclick'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b onclick="alert({@foo})">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfiltered()
	{
		$node = $this->loadTemplate('<b onclick="alert({@foo})">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('onclick'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b onclick="alert({.})">...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedDot()
	{
		$node = $this->loadTemplate('<b onclick="alert({.})">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('onclick'));

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #number: <b><xsl:copy-of select="@onclick"/>...</b>
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@onclick"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('onclick')->filterChain->append(new Number);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:copy-of select="@onclick"/>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'onclick'
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@onclick"/>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed if unfiltered: <b><xsl:copy-of select="@onclick"/>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'onclick' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@onclick"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('onclick');

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Allowed if #number: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Number);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknownValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="onclick"><xsl:value-of select="."/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedValueOfDot()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="."/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed: <b><xsl:attribute name="onclick"><xsl:apply-templates/></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot allow unfiltered data in this context
	*/
	public function testDisallowedApplyTemplates()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:apply-templates/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:attribute name="onclick"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess context due to 'xsl:for-each'
	*/
	public function testUnsafeContext()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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