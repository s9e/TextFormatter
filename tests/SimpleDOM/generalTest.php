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
 
class generalTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException BadMethodCallException
	*/
	public function testCallsToUnsupportedMethodsFail()
	{
		$root = new SimpleDOM('<root><child /></root>');

		try
		{
			$root->getAttributeNode('foo');
		}
		catch (Exception $e)
		{
			$this->assertSame('DOM method getAttributeNode() is not supported', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testCallsToUnsupportedPropetiesFail()
	{
		$root = new SimpleDOM('<root><child /></root>');

		try
		{
			$root->schemaTypeInfo();
		}
		catch (Exception $e)
		{
			$this->assertSame('DOM property schemaTypeInfo is not supported', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException BadMethodCallException
	*/
	public function testCallsToUnknownMethodsFail()
	{
		$root = new SimpleDOM('<root><child /></root>');

		try
		{
			$root->UNKNOWN_METHOD();
		}
		catch (Exception $e)
		{
			$this->assertSame('Undefined method SimpleDOM::UNKNOWN_METHOD()', $e->getMessage());
			throw $e;
		}
	}

	public function testTextNodesAreReturnedAsText()
	{
		$xml = new SimpleDOM('<xml>This <is /> a text</xml>');
		$this->assertSame(' a text', $xml->lastChild());
	}
}