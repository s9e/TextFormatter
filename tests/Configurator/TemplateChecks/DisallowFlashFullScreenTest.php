<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowFlashFullScreen;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractFlashRestriction
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowFlashFullScreen
*/
class DisallowFlashFullScreenTest extends Test
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
	* @testdox Disallows <embed allowFullScreen="true"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test1a()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox Allows <embed allowFullScreen="false"/>
	*/
	public function test1b()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="false"/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allows <embed/>
	*/
	public function test1d()
	{
		$node = $this->loadTemplate('<embed/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallows <embed allowFullScreen="unknown"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowFullScreen value 'unknown'
	*/
	public function test1e()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="unknown"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox Disallows <embed allowFullScreen="{@foo}"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowFullScreen setting '{@foo}'
	*/
	public function test1f()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="{@foo}"/>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox Disallows <embed allowFullScreen="false"><xsl:attribute name="allowFullScreen"/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test3()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="false"><xsl:attribute name="allowFullScreen"/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallows <object><param name="allowFullScreen" value="true"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test4a()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Allows <object><param name="allowFullScreen" value="false"/></object>
	*/
	public function test4b()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="false"/></object>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allows <object/>
	*/
	public function test4d()
	{
		$node = $this->loadTemplate('<object/>');
		$check = new DisallowFlashFullScreen;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallows <object><param name="allowFullScreen" value="unknown"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Unknown allowFullScreen value 'unknown'
	*/
	public function test4e()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="unknown"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallows <object><param name="allowFullScreen" value="{@foo}"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess allowFullScreen setting '{@foo}'
	*/
	public function test4f()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="{@foo}"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallows <object><param name="allowFullScreen" value="false"><xsl:attribute name="value">true</xsl:attribute></param></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of dynamic attributes
	*/
	public function test7()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="false"><xsl:attribute name="value">true</xsl:attribute></param></object>');

		try
		{
			$check = new DisallowFlashFullScreen;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) allows <embed allowFullScreen="true" src="http://example.com/example.swf"/>
	*/
	public function test8a()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true" src="http://example.com/example.swf"/>');
		$check = new DisallowFlashFullScreen(true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox DisallowFlashFullScreen(false) disallows <embed allowFullScreen="true" src="http://example.com/example.swf"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test8b()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true" src="http://example.com/example.swf"/>');

		try
		{
			$check = new DisallowFlashFullScreen(false);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true" src="{@url}"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test8c()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true" src="{@url}"/>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true"><xsl:copy-of select="@src"/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test8d()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true"><xsl:copy-of select="@src"/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) allows <object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>
	*/
	public function test9a()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>');
		$check = new DisallowFlashFullScreen(true);
		$check->check($node, new Tag);
	}

	/**
	* @testdox DisallowFlashFullScreen(false) disallows <object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test9b()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="http://example.com/example.swf"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen(false);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <object><param name="allowFullScreen" value="true"/><param name="movie" value="{@url}"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test9c()
	{
		$node = $this->loadTemplate('<object><param name="allowFullScreen" value="true"/><param name="movie" value="{@url}"/></object>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowFlashFullScreen(true) disallows <embed allowFullScreen="true"><xsl:apply-templates/></embed>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowFullScreen setting 'true' exceeds restricted value 'false'
	*/
	public function test9d()
	{
		$node = $this->loadTemplate('<embed allowFullScreen="true"><xsl:apply-templates/></embed>');

		try
		{
			$check = new DisallowFlashFullScreen(true);
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->attributes->item(0));

			throw $e;
		}
	}
}