<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\ColorFilter;
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
	* @doesNotPerformAssertions
	*/
	public function testAllowedStaticElement()
	{
		$node = $this->loadTemplate('<style>.important { color:red }</style>');

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b style="color:red">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<b style="color:red">...</b>');

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #color: <b style="color:{@foo}">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<b style="color:{@foo}">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new ColorFilter);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b style="color:{@foo}">...</b>
	*/
	public function testDisallowedUnknown()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<b style="color:{@foo}">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('style')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b style="color:{@foo}">...</b>
	*/
	public function testDisallowedUnfiltered()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('style')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b style="color:{.}">...</b>
	*/
	public function testDisallowedDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<b style="color:{.}">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('style')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #color: <b><xsl:copy-of select="@style"/>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@style"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('style')->filterChain->append(new ColorFilter);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:copy-of select="@style"/>...</b>
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'style'");

		$node = $this->loadTemplate('<b><xsl:copy-of select="@style"/>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:copy-of select="@style"/>...</b>
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'style' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #color: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new ColorFilter);

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testDisallowedUnknownValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="style"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="style"><xsl:value-of select="."/></xsl:attribute>...</b>
	*/
	public function testDisallowedValueOfDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:value-of select="."/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:attribute name="style"><xsl:apply-templates/></xsl:attribute>...</b>
	*/
	public function testDisallowedApplyTemplates()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot allow unfiltered data in this context');

		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:apply-templates/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:attribute name="style"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>
	*/
	public function testUnsafeContext()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess context due to 'xsl:for-each'");

		$node = $this->loadTemplate('<b><xsl:attribute name="style"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicCSS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b style="width:{0+@foo}px">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedNumeric()
	{
		$node = $this->loadTemplate('<b style="width:{0+@foo}px">...</b>');

		$check = new DisallowUnsafeDynamicCSS;
		$check->check($node, new Tag);
	}
}