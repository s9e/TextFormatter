<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
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
	* @testdox Implements ArrayAccess
	*/
	public function testImplementsArrayAccess()
	{
		$this->assertInstanceOf('ArrayAccess', new TemplateChecker);
	}

	/**
	* @testdox Implements Iterator
	*/
	public function testImplementsIterator()
	{
		$this->assertInstanceOf('Iterator', new TemplateChecker);
	}

	/**
	* @testdox Disallows attribute sets by default
	*/
	public function testDefaultAttributeSets()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of attribute sets');

		$this->checkTemplate('<b use-attribute-sets="foo"/>');
	}

	/**
	* @testdox Disallows <xsl:copy/> by default
	*/
	public function testDefaultCopy()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of an 'xsl:copy' element");

		$this->checkTemplate('<xsl:copy/>');
	}

	/**
	* @testdox Disallows disabling output escaping by default
	*/
	public function testDefaultDisableOutputEscaping()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("The template contains a 'disable-output-escaping' attribute");

		$this->checkTemplate('<b disable-output-escaping="1"/>');
	}

	/**
	* @testdox Disallows dynamic attribute names by default
	*/
	public function testDefaultDynamicAttributeNames()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Dynamic <xsl:attribute/> names are disallowed');

		$this->checkTemplate('<b><xsl:attribute name="{@foo}"/></b>');
	}

	/**
	* @testdox Disallows dynamic element names by default
	*/
	public function testDefaultDynamicElementNames()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Dynamic <xsl:element/> names are disallowed');

		$this->checkTemplate('<xsl:element name="{@foo}"/>');
	}

	/**
	* @testdox Disallows dynamic object param names by default
	*/
	public function testDefaultDynamicObjectParamNames()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("A 'param' element with a suspect name has been found");

		$this->checkTemplate('<object><param name="{@foo"/></object>');
	}

	/**
	* @testdox Disallows PHP tags by default
	*/
	public function testDefaultPHPTags()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the template');

		$this->checkTemplate('<?php ?>');
	}

	/**
	* @testdox Disallows outputing PHP tags by default
	*/
	public function testDefaultGeneratedPHPTags()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('PHP tags are not allowed in the output');

		$this->checkTemplate('<b><xsl:processing-instruction name="php"/></b>');
	}

	/**
	* @testdox Disallows potentially unsafe <xsl:copy-of/> by default
	*/
	public function testDefaultCopyOf()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of 'xsl:copy-of' select expression 'FOO'");

		$this->checkTemplate('<b><xsl:copy-of select="FOO"/></b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic CSS by default
	*/
	public function testDefaultDynamicCSS()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$this->checkTemplate('<b style="color:{@foo}">...</b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic JS by default
	*/
	public function testDefaultDynamicJS()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$this->checkTemplate('<b onclick="{@foo}">...</b>');
	}

	/**
	* @testdox Disallows potentially unsafe dynamic URLs by default
	*/
	public function testDefaultDynamicURL()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of unknown attribute 'foo'");

		$this->checkTemplate('<a href="{@foo}">...</a>');
	}

	/**
	* @testdox Disallows document() in XPath by default
	*/
	public function testDefaultDocumentXPath()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('An XPath expression uses the document() function');

		$this->checkTemplate('<xsl:value-of select="document(.)"/>');
	}

	/**
	* @testdox Restricts Flash's allowScriptAccess to "sameDomain" (or lower) by default, in objects that use dynamic values
	*/
	public function testDefaultFlashScriptAccess()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("allowScriptAccess setting 'always' exceeds restricted value 'sameDomain'");

		$templateChecker = new TemplateChecker;

		$tag = new Tag;
		$tag->attributes->add('url')->filterChain->append(new UrlFilter);
		$tag->template = '<embed allowScriptAccess="always" src="{@url}"/>';

		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox Disallows <sax:output/> by default
	* @link https://bugs.php.net/bug.php?id=54446
	*/
	public function testDefaultSaxOutput()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Element 'sax:output' is disallowed");

		$this->checkTemplate('<sax:output xmlns:sax="http://icl.com/saxon" />');
	}

	/**
	* @testdox checkTag() doesn't do anything if the template is safe
	* @doesNotPerformAssertions
	*/
	public function testSafe()
	{
		$tag = new Tag;
		$tag->template = '<br/>';

		$templateChecker = new TemplateChecker;
		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox checkTag() doesn't check templates that are marked as unsafe
	* @doesNotPerformAssertions
	*/
	public function testUnsafeTemplate()
	{
		$tag = new Tag;
		$tag->template = new UnsafeTemplate('<b disable-output-escaping="1"/>');

		$templateChecker = new TemplateChecker;
		$templateChecker->checkTag($tag);
	}

	/**
	* @testdox Can be reset and reconfigured with different checks
	* @depends testDefaultDisableOutputEscaping
	* @doesNotPerformAssertions
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

	/**
	* @testdox disable() disables all checks
	* @depends testDefaultDisableOutputEscaping
	* @doesNotPerformAssertions
	*/
	public function testDisable()
	{
		$template = '<b disable-output-escaping="1"/>';

		$templateChecker = new TemplateChecker;
		$templateChecker->disable();
		$templateChecker->checkTemplate($template);
	}

	/**
	* @testdox enable() re-enables all checks
	* @depends testDefaultDisableOutputEscaping
	*/
	public function testEnable()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');

		$template = '<b disable-output-escaping="1"/>';

		$templateChecker = new TemplateChecker;
		$templateChecker->disable();
		$templateChecker->enable();
		$templateChecker->checkTemplate($template);
	}
}