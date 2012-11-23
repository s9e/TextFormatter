<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use DOMDocument;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\Repository;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\Repository
*/
class RepositoryTest extends Test
{
	/**
	* @testdox __construct() accepts the path to an XML file as argument
	*/
	public function testConstructorFile()
	{
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');
	}

	/**
	* @testdox __construct() accepts a DOMDocument as argument
	*/
	public function testConstructorDOMDocument()
	{
		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');

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
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');
		$repository->get('FOOBAR');
	}

	/**
	* @testdox get() normalizes the BBCode name before retrieval
	*/
	public function testNameIsNormalized()
	{
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');
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
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO]</usage>
					<template>&lt;b&gt;<var name="text">foo</var>&lt;/b&gt;</template>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom);
		$config = $repository->get('FOO');

		$this->assertSame(
			'<b>foo</b>',
			$config['tag']->defaultTemplate
		);
	}

	/**
	* @testdox Custom tagName is correctly set
	*/
	public function testCustomTagName()
	{
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');
		$config = $repository->get('*');

		$this->assertSame(
			'LI',
			$config['bbcode']->tagName
		);
	}

	/**
	* @testdox Rules targetting tags are correctly set
	*/
	public function testTargettingRules()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO]</usage>
					<template></template>
					<rules>
						<allowChild>BAR</allowChild>
						<allowChild>BAZ</allowChild>
						<defaultChildRule>deny</defaultChildRule>
					</rules>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom);
		$config = $repository->get('FOO');

		$this->assertEquals(
			array('BAR', 'BAZ'),
			$config['tag']->rules['allowChild']
		);

		$this->assertSame('deny', $config['tag']->rules['defaultChildRule']);
	}

	/**
	* @testdox Boolean rules are set to their default value
	*/
	public function testBooleanRules()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO]</usage>
					<template></template>
					<rules>
						<denyAll />
					</rules>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom);
		$config = $repository->get('FOO');

		$this->assertTrue($config['tag']->rules['denyAll']);
	}
}