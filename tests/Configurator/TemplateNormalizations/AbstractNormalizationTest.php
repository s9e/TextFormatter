<?php

namespace s9e\TextFormatter\Tests\Configurator;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization
*/
class AbstractNormalizationTest extends Test
{
	protected function getNormalization($query = null)
	{
		return new TestNormalization($this, $query);
	}

	protected function getMockNormalization($query)
	{
		return $this->getMockBuilder(__NAMESPACE__ . '\\TestNormalization')
			->setConstructorArgs([$this, $query])
			->setMethods(['normalizeAttribute', 'normalizeElement'])
			->getMock();
	}

	protected function getTemplateElement()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<xsl:template xmlns:xsl="' . AbstractNormalization::XMLNS_XSL . '">
				<div data-bar="BAR" data-foo="FOO"/>
				<span/><span/>
			</xsl:template>'
		);

		return $dom->documentElement;
	}

	/**
	* @testdox Nothing happens if the normalization doesn't override any method or set any query
	* @doesNotPerformAssertions
	*/
	public function testNothingHappens()
	{
		$normalization = $this->getNormalization();
		$normalization->normalize($this->getTemplateElement());
	}

	/**
	* @testdox Nothing happens if the normalization doesn't override any method but set an element query
	* @doesNotPerformAssertions
	*/
	public function testNothingHappensElement()
	{
		$normalization = $this->getNormalization('//*');
		$normalization->normalize($this->getTemplateElement());
	}

	/**
	* @testdox Nothing happens if the normalization doesn't override any method but set an attribute query
	* @doesNotPerformAssertions
	*/
	public function testNothingHappensAttribute()
	{
		$normalization = $this->getNormalization('//@*');
		$normalization->normalize($this->getTemplateElement());
	}

	/**
	* @testdox Calls normalizeAttribute() if an XPath query returns a DOMElement
	*/
	public function testCallsNormalizeAttribute()
	{
		$mock = $this->getMockNormalization('//@data-foo');
		$mock->expects($this->once())
		     ->method('normalizeAttribute');
		$mock->expects($this->never())
		     ->method('normalizeElement');
		$mock->normalize($this->getTemplateElement());
	}

	/**
	* @testdox Calls normalizeElement() if an XPath query returns a DOMElement
	*/
	public function testCallsNormalizeElement()
	{
		$mock = $this->getMockNormalization('//div');
		$mock->expects($this->never())
		     ->method('normalizeAttribute');
		$mock->expects($this->once())
		     ->method('normalizeElement');
		$mock->normalize($this->getTemplateElement());
	}

	/**
	* @testdox Nodes removed during normalization are not processed further
	*/
	public function testRemovedNodesAreIgnored()
	{
		$mock = $this->getMockNormalization('//span');
		$mock->expects($this->once())
		     ->method('normalizeElement')
		     ->will($this->returnCallback([$this, 'removeNextSibling']));
		$mock->normalize($this->getTemplateElement());
	}

	public function removeNextSibling($node)
	{
		$node->parentNode->removeChild($node->nextSibling);
	}

	/**
	* @testdox Can create elements and text nodes
	*/
	public function testCreateNodes()
	{
		$template = $this->getTemplateElement();

		$mock = $this->getMockNormalization('//div');
		$mock->expects($this->once())
		     ->method('normalizeElement')
		     ->will($this->returnCallback([$mock, 'createNodes']));
		$mock->normalize($template);

		$this->assertXmlStringEqualsXmlString(
			'<xsl:template xmlns:xsl="' . AbstractNormalization::XMLNS_XSL . '">
				<div data-bar="BAR" data-foo="FOO"><hr/><xsl:comment/>Text</div>
				<span/><span/>
			</xsl:template>',
			$template->ownerDocument->saveXML()
		);
	}

	/**
	* @testdox isXsl() differentiates between XSL elements and others
	*/
	public function testIsXsl()
	{
		$template      = $this->getTemplateElement();
		$ownerDocument = $template->ownerDocument;

		$div = $ownerDocument->createElement('div');
		$if  = $ownerDocument->createElementNS(AbstractNormalization::XMLNS_XSL, 'xsl:if');

		$normalization = $this->getNormalization();
		$this->assertFalse($normalization->call('isXsl', [$div]));
		$this->assertTrue($normalization->call('isXsl',  [$if]));
		$this->assertTrue($normalization->call('isXsl',  [$if, 'if']));
		$this->assertFalse($normalization->call('isXsl', [$if, 'when']));
	}

	/**
	* @testdox lowercase() works
	*/
	public function testLowercase()
	{
		$normalization = $this->getNormalization();
		$this->assertSame('foo-bar3', $normalization->call('lowercase', ['FoO-bAr3']));
	}
}

class TestNormalization extends AbstractNormalization
{
	protected $test;
	public function __construct(Test $test, $query = null)
	{
		$this->test = $test;
		if (isset($query))
		{
			$this->queries = [$query];
		}
	}

	public function call($methodName, array $args)
	{
		return call_user_func_array([$this, $methodName], $args);
	}

	public function createNodes(DOMNode $node)
	{
		$node->appendChild($this->createElement('hr'));
		$node->appendChild($this->createElement('xsl:comment'));
		$node->appendChild($this->createTextNode('Text'));
	}
}