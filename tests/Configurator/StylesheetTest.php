<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Stylesheet
*/
class StylesheetTest extends Test
{
	/**
	* @testdox setOutputMethod('xml') sets the stylesheet's output method to 'xml'
	*/
	public function testSetOutputMethod()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('xml');

		$this->assertContains(' method="xml"', $stylesheet->get());
	}

	/**
	* @testdox setOutputMethod('text') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Only html and xml methods are supported
	*/
	public function testSetOutputMethodInvalid()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setOutputMethod('text');
	}

	/**
	* @testdox setWildcardTemplate() accepts a string as template
	*/
	public function testSetWildcardTemplateString()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('foo', 'FOO');
	}

	/**
	* @testdox setWildcardTemplate() accepts a callback as template
	*/
	public function testSetWildcardTemplateCallback()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('foo', function(){});
	}

	/**
	* @testdox setWildcardTemplate() accepts an instance of Template as template
	*/
	public function testSetWildcardTemplateInstance()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('foo', new Template(''));
	}

	/**
	* @testdox setWildcardTemplate() rejects anything else
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a string, a valid callback or an instance of Template
	*/
	public function testSetWildcardTemplateInvalidType()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('foo', false);
	}

	/**
	* @testdox setWildcardTemplate() sets a template with a * matching rule for given prefix
	*/
	public function testSetWildcardTemplate()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('foo', 'FOO');

		$this->assertContains(
			'<xsl:template match="foo:*">FOO</xsl:template>',
			$stylesheet->get()
		);
	}

	/**
	* @testdox setWildcardTemplate() throws an exception if the prefix is empty
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix ''
	*/
	public function testSetWildcardTemplateEmpty()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('', 'FOO');
	}

	/**
	* @testdox setWildcardTemplate() throws an exception if the prefix is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix '*invalid*'
	*/
	public function testSetWildcardTemplateInvalid()
	{
		$stylesheet = new Stylesheet(new TagCollection);
		$stylesheet->setWildcardTemplate('*invalid*', 'FOO');
	}

	/**
	* @testdox get() tests the wildcard templates' safeness
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException bar
	*/
	public function testGetUnsafeWildcard()
	{
		$tags = new TagCollection;
		$tags->add('foo:X')->attributes->add('bar');

		$stylesheet = new Stylesheet($tags);
		$stylesheet->setWildcardTemplate('foo', '<a href="{@bar}">_</a>');

		$stylesheet->get();
	}

	/**
	* @testdox get() only tests the wildcard templates' safeness against tags in its namespace
	*/
	public function testGetUnsafeWildcardWrongPrefix()
	{
		$tags = new TagCollection;
		$tags->add('X')->attributes->add('bar');
		$tags->add('bar:X')->attributes->add('bar');

		$stylesheet = new Stylesheet($tags);
		$stylesheet->setWildcardTemplate('foo', '<a href="{@bar}">_</a>');
		$stylesheet->setWildcardTemplate('bar', 'BAR');

		$stylesheet->get();
	}

	/**
	* @testdox get() only tests the wildcard templates' safeness against tags that do not have a default template
	*/
	public function testGetUnsafeWildcardDefaultTemplate()
	{
		$tags = new TagCollection;
		$tag = $tags->add('foo:X');
		$tag->attributes->add('bar');
		$tag->defaultTemplate = 'FOO';

		$stylesheet = new Stylesheet($tags);
		$stylesheet->setWildcardTemplate('foo', '<a href="{@bar}">_</a>');

		$stylesheet->get();
	}

	/**
	* @testdox get() minifies predicates
	*/
	public function testGetMinifiesPredicates()
	{
		$tags = new TagCollection;
		$tags->add('X')->templates['. = 2'] = 'BAR';

		$stylesheet = new Stylesheet($tags);

		$this->assertContains(
			'match="X[.=2]"',
			$stylesheet->get()
		);
	}

	/**
	* @testdox get() correctly escapes predicates
	*/
	public function testGetEscapesPredicates()
	{
		$tags = new TagCollection;
		$tags->add('X')->templates['.>""'] = 'BAR';

		$stylesheet = new Stylesheet($tags);

		$this->assertContains(
			'match="X[.&gt;&quot;&quot;]"',
			$stylesheet->get()
		);
	}

	/**
	* @testdox get() merges duplicate templates
	*/
	public function testGetMergesDuplicateTemplates()
	{
		$tags = new TagCollection;
		$tags->add('X')->defaultTemplate = 'X';
		$tags->add('Y')->defaultTemplate = 'X';

		$stylesheet = new Stylesheet($tags);

		$this->assertContains(
			'<xsl:template match="X|Y">X</xsl:template>',
			$stylesheet->get()
		);
	}

	/**
	* @testdox get() represents empty templates with a self-closing element
	*/
	public function testGetEmptyTemplates()
	{
		$tags = new TagCollection;

		$stylesheet = new Stylesheet($tags);

		$this->assertContains(
			'<xsl:template match="et|i|st"/>',
			$stylesheet->get()
		);
	}
}