<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction
*/
class DisallowXPathFunctionTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox Disallowed: <xsl:value-of select="document(@foo)"/>
	*/
	public function test1()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$node = $this->loadTemplate('<xsl:value-of select="document(@foo)"/>');

		try
		{
			$check = new DisallowXPathFunction('document');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <xsl:value-of select="php:function()"/>
	*/
	public function test1b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the php:function() function');

		$node = $this->loadTemplate('<xsl:value-of select="php:function()"/>');

		try
		{
			$check = new DisallowXPathFunction('php:function');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <xsl:value-of select="php : function()"/>
	*/
	public function test1c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the php:function() function');

		$node = $this->loadTemplate('<xsl:value-of select="php : function()"/>');

		try
		{
			$check = new DisallowXPathFunction('php:function');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('select')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b title="...{document()}"/>
	*/
	public function test2()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$node = $this->loadTemplate('<b title="...{document()}"/>');

		try
		{
			$check = new DisallowXPathFunction('document');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('title')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b title="...{ document () }"/>
	*/
	public function test3()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$node = $this->loadTemplate('<b title="...{ document () }"/>');

		try
		{
			$check = new DisallowXPathFunction('document');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('title')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b title="...{ doc&#117;ment () }"/>
	*/
	public function test4()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$node = $this->loadTemplate('<b title="...{ doc&#117;ment () }"/>');

		try
		{
			$check = new DisallowXPathFunction('document');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('title')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b title="{concat(\'}\',document())}"/>
	*/
	public function test5()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$node = $this->loadTemplate('<b title="{concat(\'}\',document())}"/>');

		try
		{
			$check = new DisallowXPathFunction('document');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('title')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b title="document()"/>
	* @doesNotPerformAssertions
	*/
	public function test6()
	{
		$node = $this->loadTemplate('<b title="document()"/>');

		$check = new DisallowXPathFunction('document');
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b title="{&quot;document()&quot;}"/>
	* @doesNotPerformAssertions
	*/
	public function test7()
	{
		$node = $this->loadTemplate('<b title="{&quot;document()&quot;}"/>');

		$check = new DisallowXPathFunction('document');
		$check->check($node, new Tag);
	}
}