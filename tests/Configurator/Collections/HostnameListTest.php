<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\HostnameList
*/
class HostnameListTest extends Test
{
	/**
	* @testdox asConfig() returns null if the collection is empty
	*/
	public function testAsConfigNull()
	{
		$list = new HostnameList;

		$this->assertNull($list->asConfig());
	}

	/**
	* @testdox asConfig() returns a Regexp
	*/
	public function testAsConfigRegexp()
	{
		$list = new HostnameList;
		$list->add('example.org');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Regexp',
			$list->asConfig()
		);
	}

	/**
	* @testdox asConfig() returns a regexp that matches its hostnames
	*/
	public function testAsConfigRegexpMatch()
	{
		$list = new HostnameList;
		$list->add('example.org');

		$this->assertMatchesRegularExpression(
			(string) $list->asConfig(),
			'example.org'
		);
	}

	/**
	* @requires function idn_to_ascii
	* @testdox IDNs are punycoded if idn_to_ascii() is available
	*/
	public function testIDNsArePunycoded()
	{
		$list = new HostnameList;
		$list->add('pаypal.com');

		$this->assertStringContainsString(
			'xn--pypal-4ve\\.com',
			(string) $list->asConfig()
		);
	}

	/**
	* @testdox add('*.example.org') matches 'www.example.org'
	*/
	public function testWildcardStart()
	{
		$list = new HostnameList;
		$list->add('*.example.org');

		$this->assertMatchesRegularExpression(
			(string) $list->asConfig(),
			'www.example.org'
		);
	}

	/**
	* @testdox add('example.org') does not match 'www.example.org'
	*/
	public function testNoWildcardStart()
	{
		$list = new HostnameList;
		$list->add('example.org');

		$this->assertDoesNotMatchRegularExpression(
			(string) $list->asConfig(),
			'www.example.org'
		);
	}

	/**
	* @testdox add('example.*') matches 'example.org'
	*/
	public function testWildcardEnd()
	{
		$list = new HostnameList;
		$list->add('example.*');

		$this->assertMatchesRegularExpression(
			(string) $list->asConfig(),
			'example.org'
		);
	}

	/**
	* @testdox add('example') does not match 'example.org'
	*/
	public function testNoWildcardEnd()
	{
		$list = new HostnameList;
		$list->add('example');

		$this->assertDoesNotMatchRegularExpression(
			(string) $list->asConfig(),
			'example.org'
		);
	}
}