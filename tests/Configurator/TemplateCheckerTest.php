<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecker
*/
class TemplateCheckerTest extends Test
{
	protected function checkTemplate($template)
	{
		$templateChecker = new TemplateChecker;
		$templateChecker->checkTemplate($template);
	}

	/**
	* @testdox Disallows attribute sets by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of attribute sets
	*/
	public function testDefaultAttributeSets()
	{
		$this->checkTemplate('<b use-attribute-sets="foo"/>');
	}

	/**
	* @testdox Disallows <xsl:copy/> by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of an 'xsl:copy' element
	*/
	public function testDefaultCopy()
	{
		$this->checkTemplate('<xsl:copy/>');
	}

	/**
	* @testdox Disallows disabling output escaping by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage The template contains a 'disable-output-escaping' attribute
	*/
	public function testDefaultDisableOutputEscaping()
	{
		$this->checkTemplate('<b disable-output-escaping="1"/>');
	}

	/**
	* @testdox Disallows dynamic attribute names by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Dynamic <xsl:attribute/> names are disallowed
	*/
	public function testDefaultDynamicAttributeNames()
	{
		$this->checkTemplate('<b><xsl:attribute name="{@foo}"/></b>');
	}

	/**
	* @testdox Disallows dynamic element names by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Dynamic <xsl:element/> names are disallowed
	*/
	public function testDefaultDynamicElementNames()
	{
		$this->checkTemplate('<xsl:element name="{@foo}"/>');
	}

	/**
	* @testdox Disallows dynamic object param names by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage A 'param' element with a suspect name has been found
	*/
	public function testDefaultDynamicObjectParamNames()
	{
		$this->checkTemplate('<object><param name="{@foo"/></object>');
	}

	/**
	* @testdox Disallows PHP tags by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the template
	*/
	public function testDefaultPHPTags()
	{
		$this->checkTemplate('<?php ?>');
	}

	/**
	* @testdox Disallows outputing PHP tags by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage PHP tags are not allowed in the output
	*/
	public function testDefaultGeneratedPHPTags()
	{
		$this->checkTemplate('<b><xsl:processing-instruction name="php"/></b>');
	}

	/**
	* @testdox Disallows potentially unsafe <xsl:copy-of/> by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of 'xsl:copy-of' select expression 'FOO'
	*/
	public function testDefaultCopyOf()
	{
		$this->checkTemplate('<b><xsl:copy-of select="FOO"/></b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic CSS by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDefaultDynamicCSS()
	{
		$this->checkTemplate('<b style="color:{@foo}">...</b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic JS by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDefaultDynamicJS()
	{
		$this->checkTemplate('<b onclick="{@foo}">...</b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic URLs by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDefaultDynamicURL()
	{
		$this->checkTemplate('<a href="{@foo}">...</a>');
	}

	/**
	* @testdox Disallows document() in XPath by default
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage An XPath expression uses the document() function
	*/
	public function testDefaultDocumentXPath()
	{
		$this->checkTemplate('<xsl:value-of select="document(.)"/>');
	}

	/**
	* @testdox Restricts Flash's allowScriptAccess to "sameDomain" (or lower) by default, in objects that use dynamic values
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'
	*/
	public function testDefaultFlashScriptAccess()
	{
		$templateChecker = new TemplateChecker;

		$tag = new Tag;
		$tag->attributes->add('url')->filterChain->append(new Url);
		$tag->defaultTemplate = '<embed allowScriptAccess="always" src="{@url}"/>';

		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox checkTag() doesn't check templates that are marked as unsafe
	*/
	public function testUnsafeTemplate()
	{
		$tag = new Tag;
		$tag->defaultTemplate = new UnsafeTemplate('<b disable-output-escaping="1"/>');

		$templateChecker = new TemplateChecker;
		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox checkTag() checks all the templates of a tag
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage The template contains a 'disable-output-escaping' attribute
	*/
	public function testAllTemplates()
	{
		$tag = new Tag;
		$tag->templates->add('',     '<b/>');
		$tag->templates->add('@foo', '<b disable-output-escaping="1"/>/>');

		$templateChecker = new TemplateChecker;
		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox Can be reset and reconfigured with different checks
	* @depends testDefaultDisableOutputEscaping
	*/
	public function testReconfigure()
	{
		$template = '<b disable-output-escaping="1"/>';

		$templateChecker = new TemplateChecker;
		$templateChecker->clear();
		$templateChecker->checkTemplate($template);

		$templateChecker->append('DisallowDisableOutputEscaping');

		try
		{
			$templateChecker->checkTemplate($template);
		}
		catch (UnsafeTemplateException $e)
		{
			return;
		}

		$this->fail();
	}
}