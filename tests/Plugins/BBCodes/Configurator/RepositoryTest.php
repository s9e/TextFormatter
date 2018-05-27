<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey;
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
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml', new BBCodeMonkey(new Configurator));
	}

	/**
	* @testdox __construct() accepts a DOMDocument as argument
	*/
	public function testConstructorDOMDocument()
	{
		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml');

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
	}

	/**
	* @testdox __construct() throws an exception if passed anything else
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage NULL is not a valid BBCode repository file
	*/
	public function testConstructorInvalidPath()
	{
		$repository = new Repository(null, new BBCodeMonkey(new Configurator));
	}

	/**
	* @testdox __construct() throws an exception if passed the path to a file that is not valid XML
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is not a valid BBCode repository file
	*/
	public function testConstructorInvalidFile()
	{
		$repository = new Repository(__FILE__, new BBCodeMonkey(new Configurator));
	}

	/**
	* @testdox get() throws an exception if the BBCode is not in repository
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find 'FOOBAR' in repository
	*/
	public function testUnknownBBCode()
	{
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml', new BBCodeMonkey(new Configurator));
		$repository->get('FOOBAR');
	}

	/**
	* @testdox get() normalizes the name before retrieval
	*/
	public function testNameIsNormalized()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="B">
					<usage>[FOO]{TEXT}[/FOO]</usage>
					<template/>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
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

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO', ['attrName' => 'bar']);

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

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO', ['text' => 'Hello']);

		$this->assertSame('Hello', (string) $config['tag']->template);
	}

	/**
	* @testdox Multiple variables can be replaced
	*/
	public function testMultipleVars()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="FOO">
					<usage>[FOO <var name="attr1">attr1</var>={TEXT1} <var name="attr2">attr2</var>={TEXT2}]{TEXT}[/FOO]</usage>
					<template><var name="tpl" /></template>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO', [
			'attr1' => 'x',
			'attr2' => 'y',
			'tpl'   => 'Hello'
		]);

		$this->assertTrue(isset($config['tag']->attributes['x']));
		$this->assertTrue(isset($config['tag']->attributes['y']));
		$this->assertFalse(isset($config['tag']->attributes['attr1']));
		$this->assertFalse(isset($config['tag']->attributes['attr2']));
		$this->assertSame('Hello', (string) $config['tag']->template);
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

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO');

		$this->assertSame(
			'<b>foo</b>',
			(string) $config['tag']->template
		);
	}

	/**
	* @testdox Variables are only replaced for current invocation
	*/
	public function testVarsAreTemporary()
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

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));

		$config = $repository->get('FOO', ['text' => 'bar']);
		$this->assertSame(
			'<b>bar</b>',
			(string) $config['tag']->template
		);

		$config = $repository->get('FOO');
		$this->assertSame(
			'<b>foo</b>',
			(string) $config['tag']->template
		);
	}

	/**
	* @testdox Custom tagName is correctly set
	*/
	public function testCustomTagName()
	{
		$repository = new Repository(__DIR__ . '/../../../../src/Plugins/BBCodes/Configurator/repository.xml', new BBCodeMonkey(new Configurator));
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
					</rules>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO');

		$this->assertEquals(
			['BAR', 'BAZ'],
			$config['tag']->rules['allowChild']
		);
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
						<ignoreTags />
					</rules>
				</bbcode>
			</repository>'
		);

		$repository = new Repository($dom, new BBCodeMonkey(new Configurator));
		$config = $repository->get('FOO');

		$this->assertTrue($config['tag']->rules['ignoreTags']);
	}
}