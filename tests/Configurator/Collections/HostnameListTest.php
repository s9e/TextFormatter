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
	* @testdox asConfig() returns a Variant
	*/
	public function testAsConfigVariant()
	{
		$list = new HostnameList;
		$list->add('example.org');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$list->asConfig()
		);
	}

	/**
	* @testdox asConfig() returns a Variant that contains a regexp that matches its hostnames
	*/
	public function testAsConfigRegexp()
	{
		$list = new HostnameList;
		$list->add('example.org');

		$this->assertRegexp(
			$list->asConfig()->get(),
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

		$this->assertContains(
			'xn--pypal-4ve\\.com',
			$list->asConfig()->get()
		);
	}

	/**
	* @testdox add('*.example.org') matches 'www.example.org'
	*/
	public function testWildcardStart()
	{
		$list = new HostnameList;
		$list->add('*.example.org');

		$this->assertRegexp(
			$list->asConfig()->get(),
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

		$this->assertNotRegexp(
			$list->asConfig()->get(),
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

		$this->assertRegexp(
			$list->asConfig()->get(),
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

		$this->assertNotRegexp(
			$list->asConfig()->get(),
			'example.org'
		);
	}
}