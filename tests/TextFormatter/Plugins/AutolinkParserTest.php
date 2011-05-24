<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\AutolinkParser
*/
class AutolinkParserTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Autolink');
	}

	/**
	* @test
	*/
	public function HTTP_urls_are_linkified_by_default()
	{
		$this->assertTransformation(
			'Go to http://www.example.com for more info',
			'<rt>Go to <URL url="http://www.example.com">http://www.example.com</URL> for more info</rt>',
			'Go to <a href="http://www.example.com">http://www.example.com</a> for more info'
		);
	}

	/**
	* @test
	*/
	public function HTTPS_urls_are_linkified_by_default()
	{
		$this->assertTransformation(
			'Go to https://www.example.com for more info',
			'<rt>Go to <URL url="https://www.example.com">https://www.example.com</URL> for more info</rt>',
			'Go to <a href="https://www.example.com">https://www.example.com</a> for more info'
		);
	}

	/**
	* @test
	*/
	public function FTP_urls_are_not_linkified_by_default()
	{
		$this->assertTransformation(
			'Go to ftp://www.example.com for more info',
			'<pt>Go to ftp://www.example.com for more info</pt>',
			'Go to ftp://www.example.com for more info'
		);
	}

	/**
	* @test
	*/
	public function FTP_urls_are_linkified_if_the_scheme_has_been_allowed_in_configBuilder()
	{
		$this->cb->allowScheme('ftp');

		$this->assertTransformation(
			'Go to ftp://www.example.com for more info',
			'<rt>Go to <URL url="ftp://www.example.com">ftp://www.example.com</URL> for more info</rt>',
			'Go to <a href="ftp://www.example.com">ftp://www.example.com</a> for more info'
		);
	}

	/**
	* @test
	* @depends HTTP_urls_are_linkified_by_default
	*/
	public function Trailing_dots_are_not_linkified()
	{
		$this->assertTransformation(
			'Go to http://www.example.com. Or the kitten dies.',
			'<rt>Go to <URL url="http://www.example.com">http://www.example.com</URL>. Or the kitten dies.</rt>',
			'Go to <a href="http://www.example.com">http://www.example.com</a>. Or the kitten dies.'
		);
	}

	/**
	* @test
	* @depends HTTP_urls_are_linkified_by_default
	*/
	public function Trailing_punctuation_is_not_linkified()
	{
		$this->assertTransformation(
			'Go to http://www.example.com! Or the kitten dies.',
			'<rt>Go to <URL url="http://www.example.com">http://www.example.com</URL>! Or the kitten dies.</rt>',
			'Go to <a href="http://www.example.com">http://www.example.com</a>! Or the kitten dies.'
		);
	}

	/**
	* @test
	* @depends Trailing_punctuation_is_not_linkified
	*/
	public function Trailing_slash_is_linkified()
	{
		$this->assertTransformation(
			'Go to http://www.example.com/!',
			'<rt>Go to <URL url="http://www.example.com/">http://www.example.com/</URL>!</rt>',
			'Go to <a href="http://www.example.com/">http://www.example.com/</a>!'
		);
	}

	/**
	* @test
	* @depends Trailing_punctuation_is_not_linkified
	*/
	public function Trailing_equal_sign_is_linkified()
	{
		$this->assertTransformation(
			'Go to http://www.example.com/?q=!',
			'<rt>Go to <URL url="http://www.example.com/?q=">http://www.example.com/?q=</URL>!</rt>',
			'Go to <a href="http://www.example.com/?q=">http://www.example.com/?q=</a>!'
		);
	}

	/**
	* @test
	* @depends HTTP_urls_are_linkified_by_default
	*/
	public function Balanced_right_parentheses_are_linkified()
	{
		$this->assertTransformation(
			'Mars (http://en.wikipedia.org/wiki/Mars_(planet)) is the fourth planet from the Sun',
			'<rt>Mars (<URL url="http://en.wikipedia.org/wiki/Mars_(planet)">http://en.wikipedia.org/wiki/Mars_(planet)</URL>) is the fourth planet from the Sun</rt>',
			'Mars (<a href="http://en.wikipedia.org/wiki/Mars_(planet)">http://en.wikipedia.org/wiki/Mars_(planet)</a>) is the fourth planet from the Sun'
		);
	}

	/**
	* @test
	* @depends HTTP_urls_are_linkified_by_default
	*/
	public function Non_balanced_right_parentheses_are_not_linkified()
	{
		$this->assertTransformation(
			'Mars (http://en.wikipedia.org/wiki/Mars) can mean many things',
			'<rt>Mars (<URL url="http://en.wikipedia.org/wiki/Mars">http://en.wikipedia.org/wiki/Mars</URL>) can mean many things</rt>',
			'Mars (<a href="http://en.wikipedia.org/wiki/Mars">http://en.wikipedia.org/wiki/Mars</a>) can mean many things'
		);
	}
}