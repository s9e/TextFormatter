<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction
*/
class DisallowXPathFunctionTest extends Test
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
	* @testdox Disallowed: <xsl:value-of select="document(@foo)"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function test1()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the php:function() function
	*/
	public function test1b()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the php:function() function
	*/
	public function test1c()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function test2()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function test3()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function test4()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function test5()
	{
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
	*/
	public function test6()
	{
		$node = $this->loadTemplate('<b title="document()"/>');

		$check = new DisallowXPathFunction('document');
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b title="{&quot;document()&quot;}"/>
	*/
	public function test7()
	{
		$node = $this->loadTemplate('<b title="{&quot;document()&quot;}"/>');

		$check = new DisallowXPathFunction('document');
		$check->check($node, new Tag);
	}
}