<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../SimpleDOM.php';
 
class XSLTTest extends \PHPUnit_Framework_TestCase
{
	public function testXSLTProcessor()
	{
		$xml = new SimpleDOM('<xml><child>CHILD</child></xml>');

		$this->assertXmlStringEqualsXmlString(
			'<output><child>Content: CHILD</child></output>',
			$xml->XSLT($this->filepath, false)
		);
	}

	public function testXSLCache()
	{
		if (!extension_loaded('xslcache'))
		{
			$this->markTestSkipped('The XSL Cache extension is not available');
			return;
		}

		$xml = new SimpleDOM('<xml><child>CHILD</child></xml>');

		$this->assertXmlStringEqualsXmlString(
			'<output><child>Content: CHILD</child></output>',
			$xml->XSLT($this->filepath, true)
		);
	}

	/**
	* Internal stuff
	*/
	public function setUp()
	{
		$this->filepath = sys_get_temp_dir() . '/SimpleDOM_TestCase_XSLT.xsl';

		file_put_contents(
			$this->filepath,
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="xml" />

				<xsl:template match="/">
					<output>
						<xsl:for-each select="//child">
							<child>Content: <xsl:value-of select="." /></child>
						</xsl:for-each>
					</output>
				</xsl:template>

			</xsl:stylesheet>'
		);
	}

	public function tearDown()
	{
		unlink($this->filepath);
	}
}