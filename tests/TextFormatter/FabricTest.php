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
		$this->assertRendering(
			'"link text":http://www.example.com',
			'<a href="http://www.example.com">link text</a>'
		);
	}

	/**
	* @depends testLink
	*/
	public function testLinkWithTitle()
	{
		$this->assertRendering(
			'"link text(with title)":http://www.example.com',
			'<a href="http://www.example.com" title="with title">link text</a>'
		);
	}

	public function testLinkWithNoUrl()
	{
		$this->assertRendering(
			'"link text(with title)"',
			'"link text(with title)"'
		);
	}

	public function testImage()
	{
		$this->assertRendering(
			'!http://example.com/img.png!',
			'<img src="http://example.com/img.png">'
		);
	}

	/**
	* @depends testImage
	*/
	public function testImageWithAltText()
	{
		$this->assertRendering(
			'!http://example.com/img.png(alt text)!',
			'<img src="http://example.com/img.png" alt="alt text" title="alt text">'
		);
	}

	/**
	* @depends testImage
	*/
	public function testImageWithLink()
	{
		$this->assertRendering(
			'!http://example.com/img.png!:http://www.example.com',
			'<a href="http://www.example.com"><img src="http://example.com/img.png"></a>'
		);
	}


	/**
	* @depends testImageWithAltText
	* @depends testImageWithLink
	*/
	public function testImageWithAltTextAndLink()
	{
		$this->assertRendering(
			'!http://example.com/img.png(alt text)!:http://www.example.com',
			'<a href="http://www.example.com"><img src="http://example.com/img.png" alt="alt text" title="alt text"></a>'
		);
	}
/*
	public function testEM()
	{
		$this->assertRendering(
			'_emphasis_',
			'<i>emphasis</i>'
		);
	}
*/
}