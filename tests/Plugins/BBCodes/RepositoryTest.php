<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use DOMDocument;
use s9e\TextFormatter\Plugins\BBCodes\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Repository;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Repository
*/
class RepositoryTest extends Test
{
	/**
	* @testdox __construct() accepts the path to an XML file as argument
	*/
	public function testConstructorFile()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');
	}

	/**
	* @testdox __construct() accepts a DOMDocument as argument
	*/
	public function testConstructorDOMDocument()
	{
		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');

		$repository = new Repository($dom);
	}

	/**
	* @testdox __construct() throws an exception if passed anything else
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not a DOMDocument or the path to a repository file
	*/
	public function testConstructorInvalidPath()
	{
		$repository = new Repository(null);
	}

	/**
	* @testdox __construct() throws an exception if passed the path to a file that is not valid XML
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid repository file
	*/
	public function testConstructorInvalidFile()
	{
		$repository = new Repository(__FILE__);
	}

	/**
	* @testdox get() throws an exception if the BBCode is not in repository
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find BBCode 'FOOBAR' in repository
	*/
	public function testUnknownBBCode()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');
		$repository->get('FOOBAR');
	}

	/**
	* @testdox get() normalizes the BBCode name before retrieval
	*/
	public function testNameIsNormalized()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');
		$repository->get('b');
	}

	/**
	* @testdox Variables in <usage/> are replaced
	*/
	public function testReplacedUsageVars()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO <var name="attrName"/>={TEXT}]</usage>
					<template/>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom);
		$config = $repository->get('FOO', array('attrName' => 'bar'));

		$this->assertTrue(isset($config['tag']->attributes['bar']));
	}

	/**
	* @testdox Variables in <template/> are replaced
	*/
	public function testReplacedTemplateVars()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO]</usage>
					<template><var name="text"/></template>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom);
		$config = $repository->get('FOO', array('text' => 'Hello'));

		$this->assertSame('Hello', $config['tag']->defaultTemplate);
	}

	/**
	* @testdox Variables that are not replaced are left intact
	*/
	public function testUnreplacedVars()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');
		$config = $repository->get('QUOTE');

		$this->assertSame(
			'<blockquote><div><xsl:if test="@author"><cite><xsl:value-of select="@author"/> wrote:</cite></xsl:if><xsl:apply-templates/></div></blockquote>',
			$config['tag']->defaultTemplate
		);
	}

	/**
	* @testdox Custom tagName is correctly set
	*/
	public function testCustomTagName()
	{
		$repository = new Repository(__DIR__ . '/../../../src/Plugins/BBCodes/repository.xml');
		$config = $repository->get('*');

		$this->assertSame(
			'LI',
			$config['bbcode']->tagName
		);
	}
}