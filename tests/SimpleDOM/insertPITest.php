<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class insertPITest extends \PHPUnit_Framework_TestCase
{
	public function testDefaultModeIsBefore()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<?test ?><root />';

		$return = $root->insertPI('test');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testAppend()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<root><?test ?></root>';

		$return = $root->insertPI('test', null, 'append');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testAfter()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<root /><?test ?>';

		$return = $root->insertPI('test', null, 'after');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testNoData()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<?xml-stylesheet?><root />';

		$return = $root->insertPI('xml-stylesheet', null, 'before');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testString()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<?xml-stylesheet type="text/xsl" href="foo.xsl"?><root />';

		$return = $root->insertPI('xml-stylesheet', 'type="text/xsl" href="foo.xsl"', 'before');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testArray()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<?xml-stylesheet type="text/xsl" href="foo.xsl"?><root />';

		$return = $root->insertPI('xml-stylesheet', array(
			'type' => 'text/xsl',
			'href' => 'foo.xsl'
		), 'before');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	public function testMultiple()
	{
		$root = new SimpleDOM('<root />');
		$expected_xml = '<?xml-stylesheet type="text/xsl" href="foo.xsl"?><?xml-stylesheet type="text/xsl" href="bar.xsl"?><root />';

		$root->insertPI('xml-stylesheet', 'type="text/xsl" href="foo.xsl"', 'before');
		$root->insertPI('xml-stylesheet', 'type="text/xsl" href="bar.xsl"', 'before');

		$this->assertXmlStringEqualsXmlString($root->asXML(), $expected_xml);
	}

	/**
	* @expectedException DOMException
	*/
	public function testInvalidTarget()
	{
		$root = new SimpleDOM('<root />');

		try
		{
			$root->insertPI('$$$', 'type="text/xsl" href="foo.xsl"');
		}
		catch (DOMException $e)
		{
			$this->assertSame($e->code, DOM_INVALID_CHARACTER_ERR);
			throw $e;
		}
	}
}