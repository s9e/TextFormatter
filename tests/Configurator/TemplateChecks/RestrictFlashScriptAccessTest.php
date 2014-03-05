<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess
*/
class RestrictFlashScriptAccessTest extends Test
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
	* @testdox 'sameDomain' disallows <embed allowScriptAccess="always"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'
	*/
	public function test1a()
	{
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
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="sameDomain"/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <embed allowScriptAccess="never"/>
	*/
	public function test1c()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="never"/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <embed/>
	*/
	public function test1d()
	{
		$node = $this->loadTemplate('<embed/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <embed allowScriptAccess="unknown"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowScriptAccess value 'unknown'
	*/
	public function test1e()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowScriptAccess setting '{@foo}'
	*/
	public function test1f()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'always' exceeds restricted value 'never'
	*/
	public function test2a()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test2b()
	{
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
	*/
	public function test2c()
	{
		$node = $this->loadTemplate('<embed allowScriptAccess="never"/>');
		$check = new RestrictFlashScriptAccess('never');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never' disallows <embed/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test2d()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test3()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'
	*/
	public function test4a()
	{
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
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="sameDomain"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="never"/></object>
	*/
	public function test4c()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="never"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object/>
	*/
	public function test4d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="unknown"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowScriptAccess value 'unknown'
	*/
	public function test4e()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowScriptAccess setting '{@foo}'
	*/
	public function test4f()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'
	*/
	public function test5a()
	{
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
	*/
	public function test5b()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="sameDomain"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object><param name="allowScriptAccess" value="never"/></object>
	*/
	public function test5c()
	{
		$node = $this->loadTemplate('<object><param name="allowScriptAccess" value="never"/></object>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' allows <object/>
	*/
	public function test5d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new RestrictFlashScriptAccess('sameDomain');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'sameDomain' disallows <object><param name="allowScriptAccess" value="unknown"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowScriptAccess value 'unknown'
	*/
	public function test5e()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test6()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test7()
	{
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
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');
		$check = new RestrictFlashScriptAccess('never', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never',false disallows <embed src="http://example.com/example.swf"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test8b()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test8c()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test8d()
	{
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
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new RestrictFlashScriptAccess('never', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'never',false disallows <object><param name="movie" value="http://example.com/example.swf"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test9b()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test9c()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'sameDomain' exceeds restricted value 'never'
	*/
	public function test9d()
	{
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