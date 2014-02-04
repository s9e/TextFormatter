<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashNetworking;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashNetworking
*/
class RestrictFlashNetworkingTest extends Test
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
	* @testdox 'internal' disallows <embed allowNetworking="all"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'internal'
	*/
	public function test1a()
	{
		$node = $this->loadTemplate('<embed allowNetworking="all"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'internal' allows <embed allowNetworking="internal"/>
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <embed allowNetworking="none"/>
	*/
	public function test1c()
	{
		$node = $this->loadTemplate('<embed allowNetworking="none"/>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <embed/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'internal'
	*/
	public function test1d()
	{
		$node = $this->loadTemplate('<embed/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <embed allowNetworking="unknown"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowNetworking value 'unknown'
	*/
	public function test1e()
	{
		$node = $this->loadTemplate('<embed allowNetworking="unknown"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <embed allowNetworking="{@foo}"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowNetworking setting '{@foo}'
	*/
	public function test1f()
	{
		$node = $this->loadTemplate('<embed allowNetworking="{@foo}"/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'none' disallows <embed allowNetworking="all"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test2a()
	{
		$node = $this->loadTemplate('<embed allowNetworking="all"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'none' disallows <embed allowNetworking="internal"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'internal' exceeds restricted value 'none'
	*/
	public function test2b()
	{
		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'none' allows <embed allowNetworking="none"/>
	*/
	public function test2c()
	{
		$node = $this->loadTemplate('<embed allowNetworking="none"/>');
		$check = new RestrictFlashNetworking('none');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none' disallows <embed/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'internal' exceeds restricted value 'none'
	*/
	public function test2d()
	{
		$node = $this->loadTemplate('<embed allowNetworking="internal"/>');

		try
		{
			$check = new RestrictFlashNetworking('none');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <embed allowNetworking="internal"><xsl:attribute name="allowNetworking"/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test3()
	{
		$node = $this->loadTemplate('<embed allowNetworking="internal"><xsl:attribute name="allowNetworking"/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="all"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'internal'
	*/
	public function test4a()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="all"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="internal"/></object>
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="internal"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="none"/></object>
	*/
	public function test4c()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <object/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'internal'
	*/
	public function test4d()
	{
		$node = $this->loadTemplate('<object/>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="unknown"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowNetworking value 'unknown'
	*/
	public function test4e()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="{@foo}"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowNetworking setting '{@foo}'
	*/
	public function test4f()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="{@foo}"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="all"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'internal'
	*/
	public function test5a()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="all"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="internal"/></object>
	*/
	public function test5b()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="internal"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' allows <object><param name="allowNetworking" value="none"/></object>
	*/
	public function test5c()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"/></object>');
		$check = new RestrictFlashNetworking('internal');
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'internal' disallows <object><param name="allowNetworking" value="unknown"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowNetworking value 'unknown'
	*/
	public function test5e()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="unknown"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('internal');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none' disallows <object><xsl:if><param name="allowNetworking" value="none"/></xsl:if></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test6()
	{
		$node = $this->loadTemplate('<object><xsl:if><param name="allowNetworking" value="none"/></xsl:if></object>');

		try
		{
			$check = new RestrictFlashNetworking('none');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none' disallows <object><param name="allowNetworking" value="none"><xsl:attribute name="value">all</xsl:attribute></param></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test7()
	{
		$node = $this->loadTemplate('<object><param name="allowNetworking" value="none"><xsl:attribute name="value">all</xsl:attribute></param></object>');

		try
		{
			$check = new RestrictFlashNetworking('none');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true allows <embed src="http://example.com/example.swf"/>
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');
		$check = new RestrictFlashNetworking('none', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none',false disallows <embed src="http://example.com/example.swf"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test8b()
	{
		$node = $this->loadTemplate('<embed src="http://example.com/example.swf"/>');

		try
		{
			$check = new RestrictFlashNetworking('none', false);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true disallows <embed src="{@url}"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test8c()
	{
		$node = $this->loadTemplate('<embed src="{@url}"/>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true disallows <embed><xsl:copy-of select="@src"/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test8d()
	{
		$node = $this->loadTemplate('<embed><xsl:copy-of select="@src"/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true allows <object><param name="movie" value="http://example.com/example.swf"/></object>
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new RestrictFlashNetworking('none', true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox 'none',false disallows <object><param name="movie" value="http://example.com/example.swf"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test9b()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="http://example.com/example.swf"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('none', false);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true disallows <object><param name="movie" value="{@url}"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test9c()
	{
		$node = $this->loadTemplate('<object><param name="movie" value="{@url}"/></object>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox 'none',true disallows <embed><xsl:apply-templates/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowNetworking setting 'all' exceeds restricted value 'none'
	*/
	public function test9d()
	{
		$node = $this->loadTemplate('<embed><xsl:apply-templates/></embed>');

		try
		{
			$check = new RestrictFlashNetworking('none', true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}
}