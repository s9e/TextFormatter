<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowFlashFullScreen;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowFlashFullScreen
*/
class DisallowFlashFullScreenTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallows <embed allowFullScreen="true"/>
	*/
	public function test1a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<embed allowFullScreen="true"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allows <embed allowFullScreen="false"/>
	* @doesNotPerformAssertions
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="false"/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allows <embed/>
	* @doesNotPerformAssertions
	*/
	public function test1d()
	{
		$node = $this->loadTemplate('<embed/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallows <embed allowFullScreen="unknown"/>
	*/
	public function test1e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowFullScreen value 'unknown'");

		$node = $this->loadTemplate('<embed allowFullScreen="unknown"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallows <embed allowFullScreen="{@foo}"/>
	*/
	public function test1f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowFullScreen setting '{@foo}'");
		$node = $this->loadTemplate('<embed allowFullScreen="{@foo}"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallows <embed allowFullScreen="false"><xsl:attribute name="allowFullScreen"/></embed>
	*/
	public function test3()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<embed allowFullScreen="false"><xsl:attribute name="allowFullScreen"/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen;
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
	* @testdox Disallows <object><param name="allowFullScreen" value="true"/></object>
	*/
	public function test4a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
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
	* @testdox Allows <object><param name="allowFullScreen" value="false"/></object>
	* @doesNotPerformAssertions
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="false"/></object>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allows <object/>
	* @doesNotPerformAssertions
	*/
	public function test4d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallows <object><param name="allowFullScreen" value="unknown"/></object>
	*/
	public function test4e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowFullScreen value 'unknown'");

		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="unknown"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
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
	* @testdox Disallows <object><param name="allowFullScreen" value="{@foo}"/></object>
	*/
	public function test4f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowFullScreen setting '{@foo}'");
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="{@foo}"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
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
	* @testdox Disallows <object><param name="allowFullScreen" value="false"><xsl:attribute name="value">true</xsl:attribute></param></object>
	*/
	public function test7()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="false"><xsl:attribute name="value">true</xsl:attribute></param></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
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
	* @testdox DisallowFlashFullScreen(true) allows <embed allowFullScreen="true" src="http://example.com/example.swf"/>
	* @doesNotPerformAssertions
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true" src="http://example.com/example.swf"/>');
		$check = new DisallowFlashFullScreen(true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox DisallowFlashFullScreen(false) disallows <embed allowFullScreen="true" src="http://example.com/example.swf"/>
	*/
	public function test8b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<embed allowFullScreen="true" src="http://example.com/example.swf"/>');

		try
		{
			$check = new DisallowFlashFullScreen(false);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true" src="{@url}"/>
	*/
	public function test8c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<embed allowFullScreen="true" src="{@url}"/>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true"><xsl:copy-of select="@src"/></embed>
	*/
	public function test8d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<embed allowFullScreen="true"><xsl:copy-of select="@src"/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) allows <object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>
	* @doesNotPerformAssertions
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new DisallowFlashFullScreen(true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox DisallowFlashFullScreen(false) disallows <object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>
	*/
	public function test9b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen(false);
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
	* @testdox DisallowFlashFullScreen(true) disallows <object><param name="allowFullScreen" value="true"/><param name="movie" value="{@url}"/></object>
	*/
	public function test9c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="{@url}"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
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
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true"><xsl:apply-templates/></embed>
	*/
	public function test9d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowFullScreen setting 'true' exceeds restricted value 'false'");

		$node = $this->loadTemplate('<embed allowFullScreen="true"><xsl:apply-templates/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->attributes->item(0)
				)
			);

			throw $e;
		}
	}
}