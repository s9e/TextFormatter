<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\GenericConfig
*/
class GenericConfigTest extends Test
{
	/**
	* @test
	*/
	public function getConfig_returns_false_if_no_replacements_were_added()
	{
		$this->assertFalse($this->cb->loadPlugin('Generic')->getConfig());
	}

	/**
	* @test
	*/
	public function getConfig_creates_a_regexp_for_each_replacement()
	{
		$this->cb->Generic->addReplacement('#a#', '<b>a</b>');
		$this->cb->Generic->addReplacement('#b#', '<b>b</b>');

		$config = $this->cb->Generic->getConfig();

		$this->assertArrayHasKey('regexp', $config);
		$this->assertSame(2, count($config['regexp']));
	}

	/**
	* @test
	*/
	public function addReplacement_returns_the_name_of_the_tag_created()
	{
		$tagName = $this->cb->Generic->addReplacement('#a#', '<b>a</b>');

		$this->assertTrue($this->cb->tagExists($tagName));
	}

	/**
	* @test
	*/
	public function addReplacement_creates_attributes_named_after_Python_style_subpatterns()
	{
		$tagName = $this->cb->Generic->addReplacement('#(?P<xy>(?P<zz>a))#', '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @test
	*/
	public function addReplacement_creates_attributes_named_after_Perl_angle_brackets_style_subpatterns()
	{
		$tagName = $this->cb->Generic->addReplacement('#(?<xy>(?<zz>a))#', '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @test
	*/
	public function addReplacement_creates_attributes_named_after_Perl_single_quotes_style_subpatterns()
	{
		$tagName = $this->cb->Generic->addReplacement("#(?'xy'(?'zz'a))#", '<b>a</b>');

		$this->assertTrue($this->cb->attributeExists($tagName, 'xy'));
		$this->assertTrue($this->cb->attributeExists($tagName, 'zz'));
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regexp
	*/
	public function addReplacement_throws_an_exception_if_the_regexp_is_invalid()
	{
		$this->cb->Generic->addReplacement('invalid', '<b>a</b>');
	}
}