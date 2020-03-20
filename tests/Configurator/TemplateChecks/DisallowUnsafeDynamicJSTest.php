<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\NumberFilter;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicJS;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicJS
*/
class DisallowUnsafeDynamicJSTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox Allowed: <script>.important { alert(1) }</script>
	* @doesNotPerformAssertions
	*/
	public function testAllowedStaticElement()
	{
		$node = $this->loadTemplate('<script>alert(1)</script>');

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b onclick="alert(1)">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<b onclick="alert(1)">...</b>');

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #number: <b onclick="alert({@foo})">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<b onclick="alert({@foo})">...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new NumberFilter);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b onclick="alert({@foo})">...</b>
	*/
	public function testDisallowedUnknown()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<b onclick="alert({@foo})">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('onclick')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b onclick="alert({@foo})">...</b>
	*/
	public function testDisallowedUnfiltered()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('onclick')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b onclick="alert({.})">...</b>
	*/
	public function testDisallowedDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<b onclick="alert({.})">...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('onclick')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #number: <b><xsl:copy-of select="@onclick"/>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@onclick"/>...</b>');

		$tag = new Tag;
		$tag->attributes->add('onclick')->filterChain->append(new NumberFilter);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:copy-of select="@onclick"/>...</b>
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'onclick'");

		$node = $this->loadTemplate('<b><xsl:copy-of select="@onclick"/>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed if unfiltered: <b><xsl:copy-of select="@onclick"/>...</b>
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'onclick' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #number: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new NumberFilter);

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testDisallowedUnknownValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="onclick"><xsl:value-of select="@foo"/></xsl:attribute>...</b>
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <b><xsl:attribute name="onclick"><xsl:value-of select="."/></xsl:attribute>...</b>
	*/
	public function testDisallowedValueOfDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:value-of select="."/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed: <b><xsl:attribute name="onclick"><xsl:apply-templates/></xsl:attribute>...</b>
	*/
	public function testDisallowedApplyTemplates()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot allow unfiltered data in this context');

		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:apply-templates/></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Disallowed: <b><xsl:attribute name="onclick"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>
	*/
	public function testUnsafeContext()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess context due to 'xsl:for-each'");

		$node = $this->loadTemplate('<b><xsl:attribute name="onclick"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</b>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicJS;
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
	* @testdox Allowed: <b onclick="this.style.width={0+@foo}">...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedNumeric()
	{
		$node = $this->loadTemplate('<b onclick="this.style.width={0+@foo}">...</b>');

		$check = new DisallowUnsafeDynamicJS;
		$check->check($node, new Tag);
	}
}