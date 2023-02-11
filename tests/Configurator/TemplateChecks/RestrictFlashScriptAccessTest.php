<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess
*/
class RestrictFlashScriptAccessTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox 'sameDomain' disallows <embed allowScriptAccess="always"/>
	*/
	public function test1a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'");

		$node = $this->loadTemplate('<embed allowScriptAccess="always"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' allows <embed allowScriptAccess="sameDomain"/>
	* @doesNotPerformAssertions
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="sameDomain"/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <embed allowScriptAccess="never"/>
	* @doesNotPerformAssertions
	*/
	public function test1c()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="never"/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <embed/>
	* @doesNotPerformAssertions
	*/
	public function test1d()
	{
		$node = $this->loadTemplate('<embed/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <embed allowScriptAccess="unknown"/>
	*/
	public function test1e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowScriptAccess value 'unknown'");

		$node = $this->loadTemplate('<embed allowScriptAccess="unknown"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' disallows <embed allowScriptAccess="{@foo}"/>
@foo}'
	*/
	public function test1f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowScriptAccess setting '{");
		$node = $this->loadTemplate('<embed allowScriptAccess="{@foo}"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'never' disallows <embed allowScriptAccess="always"/>
	*/
	public function test2a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'always' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed allowScriptAccess="always"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('never');
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
	* @testdox 'never' disallows <embed allowScriptAccess="sameDomain"/>
	*/
	public function test2b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed allowScriptAccess="sameDomain"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('never');
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
	* @testdox 'never' allows <embed allowScriptAccess="never"/>
	* @doesNotPerformAssertions
	*/
	public function test2c()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="never"/>');
		$check = new RestrictFlashScriptAccess('never');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never' disallows <embed/>
	*/
	public function test2d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed allowScriptAccess="sameDomain"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('never');
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
	* @testdox Disallows <embed><xsl:attribute name="allowScriptAccess"/></embed>
	*/
	public function test3()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<embed><xsl:attribute name="allowScriptAccess"/></embed>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="always"/></object>
	*/
	public function test4a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'");

		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="always"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="sameDomain"/></object>
	* @doesNotPerformAssertions
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="sameDomain"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="never"/></object>
	* @doesNotPerformAssertions
	*/
	public function test4c()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="never"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object/>
	* @doesNotPerformAssertions
	*/
	public function test4d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="unknown"/></object>
	*/
	public function test4e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowScriptAccess value 'unknown'");

		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="{@foo}"/></object>
@foo}'
	*/
	public function test4f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowScriptAccess setting '{");
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="{@foo}"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="always"/></object>
	*/
	public function test5a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'");

		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="always"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="sameDomain"/></object>
	* @doesNotPerformAssertions
	*/
	public function test5b()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="sameDomain"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="never"/></object>
	* @doesNotPerformAssertions
	*/
	public function test5c()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="never"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object/>
	* @doesNotPerformAssertions
	*/
	public function test5d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="unknown"/></object>
	*/
	public function test5e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowScriptAccess value 'unknown'");

		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('sameDomain');
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
	* @testdox 'never' disallows <object><xsl:if><param name="allowScriptAccess" value="never"/></xsl:if></object>
	*/
	public function test6()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<object><xsl:if><param name="allowScriptAccess" value="never"/></xsl:if></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('never');
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
	* @testdox 'never' disallows <object><param name="allowScriptAccess" value="never"><xsl:attribute name="value">always</xsl:attribute></param></object>
	*/
	public function test7()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="never"><xsl:attribute name="value">always</xsl:attribute></param></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('never');
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
	* @testdox 'never',true allows <embed src="http://example.com/example.swf"/>
	* @doesNotPerformAssertions
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');
		$check = new RestrictFlashScriptAccess('never', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never',false disallows <embed src="http://example.com/example.swf"/>
	*/
	public function test8b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', false);
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
	* @testdox 'never',true disallows <embed src="{@url}"/>
	*/
	public function test8c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed src="{@url}"/>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', true);
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
	* @testdox 'never',true disallows <embed><xsl:copy-of select="@src"/></embed>
	*/
	public function test8d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed><xsl:copy-of select="@src"/></embed>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', true);
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
	* @testdox 'never',true allows <object><param name="movie" value="http://example.com/example.swf"/></object>
	* @doesNotPerformAssertions
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new RestrictFlashScriptAccess('never', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never',false disallows <object><param name="movie" value="http://example.com/example.swf"/></object>
	*/
	public function test9b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', false);
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
	* @testdox 'never',true disallows <object><param name="movie" value="{@url}"/></object>
	*/
	public function test9c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<object><param name="movie" value="{@url}"/></object>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', true);
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
	* @testdox 'never',true disallows <embed><xsl:apply-templates/></embed>
	*/
	public function test9d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'");

		$node = $this->loadTemplate('<embed><xsl:apply-templates/></embed>');

		try
		{
			$check = new RestrictFlashScriptAccess('never', true);
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