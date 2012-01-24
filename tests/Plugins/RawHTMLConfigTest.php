<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Plugins\RawHTMLConfig,
    s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\RawHTMLConfig
*/
class RawHTMLConfigTest extends Test
{
	/**
	* @testdox getConfig() returns false if no elements are allowed
	*/
	public function test_getConfig_returns_false_if_no_elements_are_allowed()
	{
		$this->assertFalse($this->cb->loadPlugin('RawHTML')->getConfig());
	}

	public function testGeneratesARegexp()
	{
		$this->cb->RawHTML->allowElement('a');

		$this->assertArrayHasKey('regexp', $this->cb->RawHTML->getConfig());
	}

	/**
	* @testdox Registers a namespace using the prefix "html" by default
	*/
	public function testDefaultNSPrefix()
	{
		$this->cb->loadPlugin('RawHTML');
		$this->assertTrue($this->cb->namespaceExists('html'));
	}

	public function testTheNamespacePrefixCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('RawHTML', null, array('namespacePrefix' => 'xxx'));
		$this->assertTrue($this->cb->namespaceExists('xxx'));
	}

	/**
	* @testdox Registers a namespace using the URI "http://www.w3.org/1999/xhtml" by default
	*/
	public function testDefaultNSURI()
	{
		$this->cb->loadPlugin('RawHTML');
		$this->assertSame(
			'http://www.w3.org/1999/xhtml',
			$this->cb->getNamespaceURI('html')
		);
	}

	/**
	* @testdox The namespace URI can be customized at loading time
	*/
	public function testTheNamespaceURICanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('RawHTML', null, array('namespaceURI' => 'urn:foo'));
		$this->assertSame(
			'urn:foo',
			$this->cb->getNamespaceURI('html')
		);
	}

	/**
	* @testdox Adds some catch-all XSL to render tags from its namespace by default
	*/
	public function testDefaultXSL()
	{
		$this->cb->loadPlugin('RawHTML');
		$this->assertContains(
			'<xsl:template match="html:*">',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox The default XSL handles custom namespace prefix and URI
	*/
	public function testDefaultXSLCustomNS()
	{
		$this->cb->loadPlugin('RawHTML', null, array(
			'namespacePrefix' => 'xxx',
			'namespaceURI' => 'urn:foo'
		));
		$this->assertContains(
			'<xsl:template match="xxx:*">',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox The catch-all XSL can be customized at loading time
	*/
	public function testCustomXSL()
	{
		$this->cb->loadPlugin('RawHTML', null, array(
			'xsl' => '<xsl:template match="html:*">FOO</xsl:template>'
		));
		$this->assertContains(
			'FOO',
			$this->cb->getXSL()
		);
	}

	public function testCreatesANamespacedTagForEachAllowedElement()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->tagExists('html:b');
	}

	public function testDoesNotAttemptToCreatesTheTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('RawHTML');
		$this->cb->addTag('html:b');
		$this->cb->RawHTML->allowElement('b');
	}

	public function testCreatesTagsInTheCorrectNamespaceIfItWasCustomized()
	{
		$this->cb->loadPlugin('RawHTML', null, array('namespacePrefix' => 'xxx'));
		$this->cb->RawHTML->allowElement('b');
		$this->cb->tagExists('xxx:b');
	}

	public function testTagNamesOfAllowedElementsAreLowercased()
	{
		$this->cb->RawHTML->allowElement('B');
		$this->cb->tagExists('html:b');
	}

	/**
	* @testdox allowElement() rejects invalid element names
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid element name '*'
	*/
	public function testRejectsInvalidElementNames()
	{
		$this->cb->RawHTML->allowElement('*');
	}

	public function testCreatesAnAttributeToTheNamespacedTagForEachAllowedAttributeOfAGivenElement()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');
		$this->assertTrue($this->cb->attributeExists('html:b', 'title'));
	}

	public function testAttributesAreLowercased()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'TITLE');
		$this->assertTrue($this->cb->attributeExists('html:b', 'title'));
	}

	/**
	* @testdox allowAttribute() throws an exception if the tag does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Element 'b' does not exist
	*/
	public function testUnknownElement()
	{
		$this->cb->RawHTML->allowAttribute('b', 'title');
	}

	/**
	* @testdox allowAttribute() rejects invalid attribute names
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name '*'
	*/
	public function testRejectsInvalidAttributeNames()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', '*');
	}

	/**
	* @testdox Attributes are created with option "required" set to false
	*/
	public function testAttributesAreNotRequired()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'title');

		$this->assertFalse($this->cb->getAttributeOption('html:b', 'title', 'required'));
	}

	/**
	* @testdox Attributes known to expect an URL as value are created with filter "#url"
	*/
	public function testKnownURLAttributes()
	{
		$this->cb->RawHTML->allowElement('a');
		$this->cb->RawHTML->allowAttribute('a', 'href');

		$this->assertSame(
			array('#url'),
			$this->cb->getAttributeOption('html:a', 'href', 'filterChain')
		);
	}

	/**
	* @testdox Other attributes are created with no filter
	*/
	public function testOtherAttributes()
	{
		$this->cb->RawHTML->allowElement('a');
		$this->cb->RawHTML->allowAttribute('a', 'title');

		$this->assertSame(array(), $this->cb->getAttributeOption('html:a', 'title', 'filterChain'));
	}

	/**
	* @testdox allowElement() throws an exception if the element name is "script"
	* @expectedException RuntimeException ALLOW_UNSAFE_ELEMENTS
	*/
	public function testScriptDisabled()
	{
		$this->cb->RawHTML->allowElement('script');
	}

	/**
	* @testdox allowElement() allows "script" elements if the ALLOW_UNSAFE_ELEMENTS flag is passed
	*/
	public function testScriptEnabled()
	{
		$this->cb->RawHTML->allowElement('script', RawHTMLConfig::ALLOW_UNSAFE_ELEMENTS);
	}

	/**
	* @testdox allowAttribute() throws an exception if the attribute's name is "onmouseover"
	* @expectedException RuntimeException ALLOW_UNSAFE_ATTRIBUTES
	*/
	public function testOnmouseoverDisabled()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'onmouseover');
	}

	/**
	* @testdox allowAttribute() allows "onmouseover" attributes if the ALLOW_UNSAFE_ATTRIBUTES flag is passed
	*/
	public function testOnmouseoverEnabled()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'onmouseover', RawHTMLConfig::ALLOW_UNSAFE_ATTRIBUTES);
	}

	/**
	* @testdox allowAttribute() throws an exception if the attribute's name is "style"
	* @expectedException RuntimeException ALLOW_UNSAFE_ATTRIBUTES
	*/
	public function testStyleDisabled()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'style');
	}

	/**
	* @testdox allowAttribute() allows "style" attributes if the ALLOW_UNSAFE_ATTRIBUTES flag is passed
	*/
	public function testStyleEnabled()
	{
		$this->cb->RawHTML->allowElement('b');
		$this->cb->RawHTML->allowAttribute('b', 'style', RawHTMLConfig::ALLOW_UNSAFE_ATTRIBUTES);
	}

	/**
	* @test
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/RawHTMLParser.js',
			$this->cb->RawHTML->getJSParser()
		);
	}

	/**
	* @test
	* @testdox getJSConfigMeta() marks the attribute regexp as a global regexp
	*/
	public function testJSGlobalRegexp()
	{
		$this->cb->RawHTML->allowElement('a');
		$this->cb->RawHTML->allowAttribute('a', 'href');

		$this->assertArrayMatches(
			array(
				'isGlobalRegexp' => array(
					array('attrRegexp')
				)
			),
			$this->cb->RawHTML->getJSConfigMeta()
		);
	}
}