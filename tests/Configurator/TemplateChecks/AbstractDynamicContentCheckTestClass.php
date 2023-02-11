<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
*/
class AbstractDynamicContentCheckTestClass extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Stylesheet parameters are considered safe
	* @doesNotPerformAssertions
	*/
	public function testTemplateParameter()
	{
		$node = $this->loadTemplate('<b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to an unknown attribute are unsafe
	*/
	public function testVarUnknown()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to an unknown attribute are ignored if ignoreUnknownAttributes() is called
	* @doesNotPerformAssertions
	*/
	public function testVarUnknownIgnored()
	{
		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->ignoreUnknownAttributes();
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to an unknown attribute are detected if detectUnknownAttributes() is called after ignoreUnknownAttributes()
	*/
	public function testVarUnknownDetected()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<xsl:variable name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->ignoreUnknownAttributes();
		$check->detectUnknownAttributes();
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to a safe attribute are safe
	* @doesNotPerformAssertions
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
	*/
	public function testVarUnsafe()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->lastChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Variables pointing to a safe variable are safe
	* @doesNotPerformAssertions
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
	*/
	public function testVarVarUnsafe()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

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
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->lastChild->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Variables pointing to a stylesheet parameter are safe
	* @doesNotPerformAssertions
	*/
	public function testVarParamSafe()
	{
		$node = $this->loadTemplate('<xsl:variable name="foo" select="$FOO"/><xsl:variable name="var" select="$foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Variables pointing to a stylesheet parameter of the same name are safe
	* @doesNotPerformAssertions
	*/
	public function testVarParamSameName()
	{
		$node = $this->loadTemplate('<xsl:variable name="FOO" select="$FOO"/><b><xsl:value-of select="$FOO"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Local parameters pointing to an unknown attribute are unsafe
	*/
	public function testParamUnknown()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$node = $this->loadTemplate('<xsl:param name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$check = new DummyContentCheck;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Local parameters pointing to a safe attribute are safe
	* @doesNotPerformAssertions
	*/
	public function testParamSafe()
	{
		$node = $this->loadTemplate('<xsl:param name="var" select="@foo"/><b><xsl:value-of select="$var"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Attributes are not safe if the tag's filterChain is cleared
	*/
	public function testUnfilteredAttribute()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<b><xsl:value-of select="@foo"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;
		$tag->filterChain->clear();

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Attributes are not safe if the tag's filterChain does not contain the default attribute filter
	*/
	public function testMisfilteredAttribute()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'foo' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<b><xsl:value-of select="@foo"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;
		$tag->filterChain->clear();

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Attributes can be safe with the tag's default filterChain
	* @doesNotPerformAssertions
	*/
	public function testFilteredAttribute()
	{
		$node = $this->loadTemplate('<b><xsl:value-of select="@foo"/></b>');

		$tag = new Tag;
		$tag->attributes->add('foo')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Multiple attributes can be safe
	* @doesNotPerformAssertions
	*/
	public function testCopyOfAttributesSafe()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@safe1|@safe2"/></b>');

		$tag = new Tag;
		$tag->attributes->add('safe1')->defaultValue = 1;
		$tag->attributes->add('safe2')->defaultValue = 1;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}

	/**
	* @testdox Multiple attributes can be unsafe
	*/
	public function testCopyOfAttributesUnsafe()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Attribute 'unsafe' is not properly sanitized to be used in this context");

		$node = $this->loadTemplate('<b><xsl:copy-of select="@safe|@unsafe"/></b>');

		$tag = new Tag;
		$tag->attributes->add('safe')->defaultValue   = 1;
		$tag->attributes->add('unsafe')->defaultValue = 0;

		$check = new DummyContentCheck;
		$check->check($node, $tag);
	}
}

class DummyContentCheck extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		return $xpath->query('//b | //xsl:copy-of');
	}

	protected function isSafe(Attribute $attribute)
	{
		// Use the attribute's default value as a flag
		return $attribute->defaultValue;
	}
}