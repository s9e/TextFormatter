<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashNetworking;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashNetworking
*/
class RestrictFlashNetworkingTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox 'internal' disallows <embed allowNetworking="all"/>
	*/
	public function test1a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'internal'");

		$node = $this->loadTemplate('<embed allowNetworking="all"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' allows <embed allowNetworking="internal"/>
	* @doesNotPerformAssertions
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <embed allowNetworking="none"/>
	* @doesNotPerformAssertions
	*/
	public function test1c()
	{
		$node = $this->loadTemplate('<embed allowNetworking="none"/>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <embed/>
	*/
	public function test1d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'internal'");

		$node = $this->loadTemplate('<embed/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <embed allowNetworking="unknown"/>
	*/
	public function test1e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowNetworking value 'unknown'");

		$node = $this->loadTemplate('<embed allowNetworking="unknown"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <embed allowNetworking="{@foo}"/>
@foo}'
	*/
	public function test1f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowNetworking setting '{");
		$node = $this->loadTemplate('<embed allowNetworking="{@foo}"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'none' disallows <embed allowNetworking="all"/>
	*/
	public function test2a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed allowNetworking="all"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
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
	* @testdox 'none' disallows <embed allowNetworking="internal"/>
	*/
	public function test2b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'internal' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
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
	* @testdox 'none' allows <embed allowNetworking="none"/>
	* @doesNotPerformAssertions
	*/
	public function test2c()
	{
		$node = $this->loadTemplate('<embed allowNetworking="none"/>');
		$check = new RestrictFlashNetworking('none');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none' disallows <embed/>
	*/
	public function test2d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'internal' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
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
	* @testdox 'internal' disallows <embed allowNetworking="internal"><xsl:attribute name="allowNetworking"/></embed>
	*/
	public function test3()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<embed allowNetworking="internal"><xsl:attribute name="allowNetworking"/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="all"/></object>
	*/
	public function test4a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'internal'");

		$node = $this->loadTemplate('<object><param name="allowNetworking" value="all"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' allows <object><param name="allowNetworking" value="all"/></object> if onlyIfDynamic is TRUE
	* @doesNotPerformAssertions
	*/
	public function test4aIfDynamic()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="all"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->onlyIfDynamic = true;
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="internal"/></object>
	* @doesNotPerformAssertions
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="internal"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="none"/></object>
	* @doesNotPerformAssertions
	*/
	public function test4c()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <object/>
	*/
	public function test4d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'internal'");

		$node = $this->loadTemplate('<object/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="unknown"/></object>
	*/
	public function test4e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowNetworking value 'unknown'");

		$node = $this->loadTemplate('<object><param name="allowNetworking" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="{@foo}"/></object>
@foo}'
	*/
	public function test4f()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess allowNetworking setting '{");
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="{@foo}"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="all"/></object>
	*/
	public function test5a()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'internal'");

		$node = $this->loadTemplate('<object><param name="allowNetworking" value="all"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'internal' allows <object><param name="allowNetworking" value="internal"/></object>
	* @doesNotPerformAssertions
	*/
	public function test5b()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="internal"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="none"/></object>
	* @doesNotPerformAssertions
	*/
	public function test5c()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="unknown"/></object>
	*/
	public function test5e()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Unknown allowNetworking value 'unknown'");

		$node = $this->loadTemplate('<object><param name="allowNetworking" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
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
	* @testdox 'none' disallows <object><xsl:if><param name="allowNetworking" value="none"/></xsl:if></object>
	*/
	public function test6()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<object><xsl:if><param name="allowNetworking" value="none"/></xsl:if></object>');

		try
		{
			$check = new RestrictFlashNetworking('none');
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
	* @testdox 'none' disallows <object><param name="allowNetworking" value="none"><xsl:attribute name="value">all</xsl:attribute></param></object>
	*/
	public function test7()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of dynamic attributes');

		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"><xsl:attribute name="value">all</xsl:attribute></param></object>');

		try
		{
			$check = new RestrictFlashNetworking('none');
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
	* @testdox 'none',true allows <embed src="http://example.com/example.swf"/>
	* @doesNotPerformAssertions
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');
		$check = new RestrictFlashNetworking('none', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none',false disallows <embed src="http://example.com/example.swf"/>
	*/
	public function test8b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');

		try
		{
			$check = new RestrictFlashNetworking('none', false);
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
	* @testdox 'none',true disallows <embed src="{@url}"/>
	*/
	public function test8c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed src="{@url}"/>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
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
	* @testdox 'none',true disallows <embed><xsl:copy-of select="@src"/></embed>
	*/
	public function test8d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed><xsl:copy-of select="@src"/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
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
	* @testdox 'none',true allows <object><param name="movie" value="http://example.com/example.swf"/></object>
	* @doesNotPerformAssertions
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new RestrictFlashNetworking('none', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none',false disallows <object><param name="movie" value="http://example.com/example.swf"/></object>
	*/
	public function test9b()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('none', false);
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
	* @testdox 'none',true disallows <object><param name="movie" value="{@url}"/></object>
	*/
	public function test9c()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<object><param name="movie" value="{@url}"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
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
	* @testdox 'none',true disallows <embed><xsl:apply-templates/></embed>
	*/
	public function test9d()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowNetworking setting 'all' exceeds restricted value 'none'");

		$node = $this->loadTemplate('<embed><xsl:apply-templates/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
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