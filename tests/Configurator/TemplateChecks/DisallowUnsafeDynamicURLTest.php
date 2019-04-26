<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicURL;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicURL
*/
class DisallowUnsafeDynamicURLTest extends Test
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
	* @testdox Allowed: <a href="http://example.org">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<a href="http://example.org">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #url: <a href="{@foo}">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new UrlFilter);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Allowed even if unknown: <a href="http://{@foo}">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedAnchoredScheme()
	{
		$node = $this->loadTemplate('<a href="http://{@foo}">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed even if unknown: <a href="//{@foo}">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedAnchoredSchemeless()
	{
		$node = $this->loadTemplate('<a href="//{@foo}">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed even if unknown: <a href="user/{@foo}">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedAnchoredLocalPart()
	{
		$node = $this->loadTemplate('<a href="user/{@foo}">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed even if unknown: <a href="#{@foo}">...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedAnchoredFragment()
	{
		$node = $this->loadTemplate('<a href="#{@foo}">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed if unknown: <a href="{@foo}">...</a>
	*/
	public function testDisallowedUnknown()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="{@foo}">...</a>
	*/
	public function testDisallowedUnfiltered()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="javascript:{@foo}">...</a>
	*/
	public function testDisallowedUnfilteredJavaScript()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a href="javascript:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="JAVASCRIPT:{@foo}">...</a>
	*/
	public function testDisallowedUnfilteredJavaScriptCase()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a href="JAVASCRIPT:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="<TAB>javascript:{@foo}">...</a>
	*/
	public function testDisallowedUnfilteredJavaScriptTab()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate("<a href=\"\tjavascript:{@foo}\">...</a>");

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="vbscript:{@foo}">...</a>
	*/
	public function testDisallowedUnfilteredVbcript()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a href="vbscript:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="data:{@foo}">...</a>
	*/
	public function testDisallowedUnfilteredData()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a href="data:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <a href="{.}">...</a>
	*/
	public function testDisallowedDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<a href="{.}">...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('href')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #url: <a><xsl:copy-of select="@href"/>...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;
		$tag->attributes->add('href')->filterChain->append(new UrlFilter);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:copy-of select="@href"/>...</a>
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'href'");

		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Disallowed if unfiltered: <a><xsl:copy-of select="@href"/>...</a>
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'href' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;
		$tag->attributes->add('href');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Allowed if #url: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new UrlFilter);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	*/
	public function testDisallowedUnknownValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Allowed even if unknown: <a><xsl:attribute name="href">http://<xsl:value-of select="@foo"/></xsl:attribute>...</a>
	* @doesNotPerformAssertions
	*/
	public function testAllowedValueOfAnchored()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href">http://<xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	*/
	public function testDisallowedUnknownValueOfJavaScript()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<a><xsl:attribute name="href">javascript:<xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild->firstChild->nextSibling->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Disallowed if unfiltered: <a><xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>...</a>
	*/
	public function testDisallowedValueOfDot()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of expression '.'");

		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Disallowed: <a><xsl:attribute name="href"><xsl:apply-templates/></xsl:attribute>...</a>
	*/
	public function testDisallowedApplyTemplates()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot allow unfiltered data in this context');

		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:apply-templates/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
	* @testdox Disallowed: <a><xsl:attribute name="href"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</a>
	*/
	public function testUnsafeContext()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess context due to 'xsl:for-each'");

		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
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
}