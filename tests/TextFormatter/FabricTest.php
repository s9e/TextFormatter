<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

class FabricTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Fabric');
	}

	public function testLink()
	{
		$this->assertTransformation(
			'"link text":http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>"</st>link text<et>":http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com">link text</a>'
		);
	}

	/**
	* @depends testLink
	*/
	public function testLinkWithTitle()
	{
		$this->assertTransformation(
			'"link text(with title)":http://www.example.com',
			'<rt>
				<URL title="with title" url="http://www.example.com">
					<st>"</st>link text<et>(with title)":http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com" title="with title">link text</a>'
		);
	}

	public function testLinkWithNoUrl()
	{
		$this->assertTransformation(
			'"link text(with title)"',
			'<pt>"link text(with title)"</pt>',
			'"link text(with title)"'
		);
	}

	public function testImage()
	{
		$this->assertTransformation(
			'!http://example.com/img.png!',
			'<rt>
				<IMG src="http://example.com/img.png">!http://example.com/img.png!</IMG>
			</rt>',
			'<img src="http://example.com/img.png">'
		);
	}

	/**
	* @depends testImage
	*/
	public function testImageWithAltText()
	{
		$this->assertTransformation(
			'!http://example.com/img.png(alt text)!',
			'<rt>
				<IMG alt="alt text" src="http://example.com/img.png" title="alt text">!http://example.com/img.png(alt text)!</IMG>
			</rt>',
			'<img src="http://example.com/img.png" alt="alt text" title="alt text">'
		);
	}

	/**
	* @depends testImage
	*/
	public function testImageWithLink()
	{
		$this->assertTransformation(
			'!http://example.com/img.png!:http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>!</st>
					<IMG src="http://example.com/img.png">http://example.com/img.png!</IMG>
					<et>:http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com"><img src="http://example.com/img.png"></a>'
		);
	}


	/**
	* @depends testImageWithAltText
	* @depends testImageWithLink
	*/
	public function testImageWithAltTextAndLink()
	{
		$this->assertTransformation(
			'!http://example.com/img.png(alt text)!:http://www.example.com',
			'<rt>
				<URL url="http://www.example.com">
					<st>!</st>
					<IMG alt="alt text" src="http://example.com/img.png" title="alt text">http://example.com/img.png(alt text)!</IMG>
					<et>:http://www.example.com</et>
				</URL>
			</rt>',
			'<a href="http://www.example.com"><img src="http://example.com/img.png" alt="alt text" title="alt text"></a>'
		);
	}
/*
	public function testEM()
	{
		$this->assertTransformation(
			'_emphasis_',
			'<rt>
				<URL url="http://www.example.com">
					<st>"</st>link text<et>":http://www.example.com</et>
				</URL>
			</rt>',
			'<i>emphasis</i>'
		);
	}
*/
}