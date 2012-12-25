<?php

namespace s9e\TextFormatter\Tests\Plugins\Generic;

use Exception;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Plugins\Generic\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Generic\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox add() returns the name of the tag it creates
	*/
	public function testAddReturn()
	{
		$plugin = $this->configurator->plugins->load('Generic');

		$this->assertSame(
			'GC53BB427',
			$plugin->add('/(?<foo>[0-9]+)/', '')
		);
	}

	/**
	* @testdox add() throws an exception if the regexp is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regexp
	*/
	public function testInvalidRegexp()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('invalid', '');
	}

	/**
	* @testdox add() throws an exception on duplicate named subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Duplicate named subpatterns are not allowed
	*/
	public function testDuplicateSubpatterns()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('#(?J)(?<foo>x)(?<foo>z)#', '');
	}

	/**
	* @testdox add() creates a tag to represent the replacement
	*/
	public function testCreatesTag()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<foo>[0-9]+)/', '');

		$this->assertTrue($this->configurator->tags->exists($tagName));
	}

	/**
	* @testdox add() creates an attribute for each named subpattern
	*/
	public function testCreatesAttributes()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<w>[0-9]+),(?<h>[0-9]+)/', '');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue($tag->attributes->exists('w'), "Attribute 'w' does not exist");
		$this->assertTrue($tag->attributes->exists('h'), "Attribute 'h' does not exist");
	}

	/**
	* @testdox add() creates a regexp filter for each attribute created
	*/
	public function testCreatesAttributesWithFilter()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<w>[0-9]+),(?<h>[0-9]+)/', '');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue(
			$tag->attributes->get('w')->filterChain->contains(
				ProgrammableCallback::fromArray(array(
					'callback' => new CallbackPlaceholder('#regexp'),
					'vars'     => array('regexp' => '/^(?<w>[0-9]+)$/D')
				))
			)
		);

		$this->assertTrue(
			$tag->attributes->get('h')->filterChain->contains(
				ProgrammableCallback::fromArray(array(
					'callback' => new CallbackPlaceholder('#regexp'),
					'vars'     => array('regexp' => '/^(?<h>[0-9]+)$/D')
				))
			)
		);
	}

	/**
	* @testdox An error occuring during add() does not leave a half-configured tag in the configurator's collection
	*/
	public function testErrorDuringAddDoesNotLeadToInconsistencies()
	{
		$plugin = $this->configurator->plugins->load('Generic');

		try
		{
			$plugin->add('#(?J)(?<foo>x)(?<foo>z)#', '');
		}
		catch (Exception $e)
		{
		}

		$this->assertSame(0, count($this->configurator->tags));
	}

	/**
	* @testdox asConfig() returns FALSE if no replacements were set
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox asConfig() returns the regexps in a "regexps" array where keys are tag names
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('/(?<foo>[0-9]+)/', '');
		$plugin->add('/(?<bar>[a-z]+)/', '');

		$this->assertSame(
			array(
				'regexps' => Array (
					'GC53BB427' => '/(?<foo>[0-9]+)/',
					'GDCEA6E9C' => '/(?<bar>[a-z]+)/'
				)
			),
			$plugin->asConfig()
		);
	}
}