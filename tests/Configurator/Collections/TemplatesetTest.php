<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\Templateset;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\TemplatePlaceholder;

/**
* @covers s9e\TextFormatter\Configurator\Collections\Templateset
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
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

	/**
	* @testdox set() accepts an instance of TemplatePlaceholder if it does not allow unsafe markup
	*/
	public function testSetPlaceholder()
	{
		$this->templateset->set('', new TemplatePlaceholder(function(){}));
	}

	/**
	* @testdox set() rejects an instance of TemplatePlaceholder if it allows unsafe markup
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot add unsafe template
	*/
	public function testSetPlaceholderUnsafe()
	{
		$template = new TemplatePlaceholder(function(){});
		$template->disableTemplateChecking();
		$this->templateset->set('', $template);
	}

	/**
	* @testdox setUnsafe() accepts an instance of TemplatePlaceholder and disable its template checking
	*/
	public function testSetUnsafePlaceholder()
	{
		$template = new TemplatePlaceholder(function(){});
		$this->templateset->setUnsafe('', $template);
		$this->assertTrue($template->allowsUnsafeMarkup());
	}
}