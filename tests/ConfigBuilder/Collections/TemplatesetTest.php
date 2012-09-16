<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\Templateset;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\Templateset
*/
class TemplatesetTest extends Test
{
	public function setUp()
	{
		$this->tag = new Tag;
		$this->templateset = new Templateset($this->tag);
	}

	/**
	* @testdox set() optimizes the template
	*/
	public function testSetOptimize()
	{
		$this->templateset->set('', '<b >foo</b >');

		$this->assertSame(
			'<b>foo</b>',
			$this->templateset->get('')
		);
	}

	/**
	* @testdox set() checks the template for unsafe content
	* @expectedException s9e\TextFormatter\ConfigBuilder\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage The template contains a 'disable-output-escaping' attribute
	*/
	public function testSetCheckUnsafe()
	{
		$this->templateset->set('', '<b disable-output-escaping="1"><xsl:apply-templates/></b>');
	}

	/**
	* @testdox setUnsafe() optimizes the template
	*/
	public function testSetUnsafeOptimize()
	{
		$this->templateset->setUnsafe('', '<b >foo</b >');

		$this->assertSame(
			'<b>foo</b>',
			$this->templateset->get('')
		);
	}

	/**
	* @testdox setUnsafe() does not check the template for unsafe content
	*/
	public function testSetUnsafeNoCheckUnsafe()
	{
		$xsl = '<b disable-output-escaping="1"><xsl:apply-templates/></b>';
		$this->templateset->setUnsafe('', $xsl);

		$this->assertSame(
			$xsl,
			$this->templateset->get('')
		);
	}
}