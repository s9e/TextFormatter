<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowPHPTags;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowPHPTags
*/
class DisallowPHPTagsTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox Disallowed: <b><?php ?></b>
	*/
	public function testTemplate()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the template');

		$node = $this->loadTemplate('<b><?php ?></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><?PHP ?></b>
	*/
	public function testTemplateCase()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the template');

		$node = $this->loadTemplate('<b><?PHP ?></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="php"/></b>
	*/
	public function testOutput()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the output');

		$node = $this->loadTemplate('<b><xsl:processing-instruction name="php"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="PHP"/></b>
	*/
	public function testOutputCase()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the output');

		$node = $this->loadTemplate('<b><xsl:processing-instruction name="PHP"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <b><xsl:processing-instruction name="{@foo}"/></b>
	*/
	public function testDynamic()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Dynamic processing instructions are not allowed');

		$node = $this->loadTemplate('<b><xsl:processing-instruction name="{@foo}"/></b>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b><?foo ?></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedTemplate()
	{
		$node = $this->loadTemplate('<b><?foo ?></b>');
		$check = new DisallowPHPTags;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:processing-instruction name="foo"/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedOutput()
	{
		$node = $this->loadTemplate('<b><xsl:processing-instruction name="foo"/></b>');
		$check = new DisallowPHPTags;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <script>echo "sup";</script>
	* @doesNotPerformAssertions
	*/
	public function testAllowedScript()
	{
		$node = $this->loadTemplate('<script>echo "sup";</script>');
		$check = new DisallowPHPTags;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <script language="php">echo "sup";</script>
	*/
	public function testDisallowedScript()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the template');

		$node = $this->loadTemplate('<script language="php">echo "sup";</script>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <script language="PHP">echo "sup";</script>
	*/
	public function testDisallowedScriptCaseInsensitive()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the template');

		$node = $this->loadTemplate('<script language="PHP">echo "sup";</script>');

		try
		{
			$check = new DisallowPHPTags;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild
				)
			);

			throw $e;
		}
	}
}