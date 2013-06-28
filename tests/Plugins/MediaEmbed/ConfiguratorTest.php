<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed;

use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Registers mediasites as a variable for the parser
	*/
	public function testRegistersVar()
	{
		$this->configurator->plugins->load('MediaEmbed');

		$this->assertArrayHasKey('mediasites', $this->configurator->registeredVars);
	}

	/**
	* @testdox Creates a [media] BBCode by default
	*/
	public function testMediaBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed');
		$this->assertTrue(isset($this->configurator->BBCodes['MEDIA']));
	}

	/**
	* @testdox Does not create a [media] BBCode if createBBCodes is false
	*/
	public function testNoMediaBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed', ['createBBCodes' => false]);
		$this->assertFalse(isset($this->configurator->BBCodes['MEDIA']));
	}

	/**
	* @testdox add('youtube') creates a [youtube] BBCode by default
	*/
	public function testSiteBBCode()
	{
		$this->configurator->MediaEmbed->add('youtube');
		$this->assertTrue(isset($this->configurator->BBCodes['YOUTUBE']));
	}

	/**
	* @testdox add('youtube') does not createc a [youtube] BBCode if createBBCodes is false
	*/
	public function testNoSiteBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed', ['createBBCodes' => false])->add('youtube');
		$this->assertFalse(isset($this->configurator->BBCodes['YOUTUBE']));
	}

	/**
	* @testdox add('inexistent') throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown media site 'inexistent'
	*/
	public function testAddInexistent()
	{
		$this->configurator->MediaEmbed->add('inexistent');
	}

	/**
	* @testdox add() returns a tag
	*/
	public function testAddReturn()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Tag',
			$this->configurator->MediaEmbed->add('youtube')
		);
	}

	/**
	* @testdox add() accepts an array as second argument
	*/
	public function testAddArray()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Tag',
			$this->configurator->MediaEmbed->add(
				'youtube',
				[
					'host'     => 'youtube.com',
					'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
					'template' => 'YouTube!'
				]
			)
		);
	}

	/**
	* @testdox add() creates an attribute for every named subpattern
	*/
	public function testAddAttributes()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			]
		);

		$this->assertTrue($tag->attributes->exists('path'));
		$this->assertTrue($tag->attributes->exists('id'));
	}

	/**
	* @testdox add() marks the "id" attribute as non-optional if present
	*/
	public function testAddIdRequired()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			]
		);

		$this->assertTrue($tag->attributes['id']->required);
	}

	/**
	* @testdox add() marks non-"id" attributes as optional
	*/
	public function testAddOptionalAttributes()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			]
		);

		$this->assertFalse($tag->attributes['path']->required);
	}

	/**
	* @testdox add() adds the regexp used for the "id" attribute to the list of attribute preprocessors
	*/
	public function testAddIdPreprocessor()
	{
		$r1 = "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!";

		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtube.com',
				'extract'  => $r1,
				'template' => 'YouTube!'
			]
		);

		$expected = [
			['url', new AttributePreprocessor($r1)],
			['url', new AttributePreprocessor("!^(?'id'[-0-9A-Z_a-z]+)\$!D")]
		];
		$actual = [];
		foreach ($tag->attributePreprocessors as $k => $v)
		{
			$actual[] = [$k, $v];
		}

		$this->assertEquals($expected, $actual);
	}

	/**
	* @testdox add() accepts multiple "extract" elements
	*/
	public function testAddMultipleMatch()
	{
		$r1 = "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!";
		$r2 = "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!";

		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtube.com',
				'extract'  => [$r1, $r2],
				'template' => 'YouTube!'
			]
		);

		$expected = [
			['url', new AttributePreprocessor($r1)],
			['url', new AttributePreprocessor($r2)],
			['url', new AttributePreprocessor("!^(?'id'[-0-9A-Z_a-z]+)\$!D")]
		];
		$actual = [];
		foreach ($tag->attributePreprocessors as $k => $v)
		{
			$actual[] = [$k, $v];
		}

		$this->assertEquals($expected, $actual);
	}

	/**
	* @testdox add() accepts multiple "host" elements
	*/
	public function testAddMultipleHost()
	{
		$hosts = ['youtube.com', 'youtu.be'];

		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => $hosts,
				'extract'  => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'template' => 'YouTube!'
			]
		);

		$this->assertEquals(
			$hosts,
			$this->configurator->registeredVars['mediasites']['youtube']['host']
		);
	}

	/**
	* @testdox add() sets the tag's default template to the "template" element if available
	*/
	public function testAddTemplate()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'     => 'youtu.be',
				'extract'  => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'template' => 'YouTube!'
			]
		);

		$this->assertEquals('YouTube!', $tag->defaultTemplate);
	}

	/**
	* @testdox add() sets the tag's default template to the iframe defined in the "iframe" element if available
	*/
	public function testAddIframe()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => [
					'width'  => 123,
					'height' => 456,
					'src'    => 'foo'
				]
			]
		);

		$this->assertEquals(
			'<iframe width="123" height="456" src="foo" allowfullscreen=""/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() sets the tag's default template to the object defined in the "flash" element if available
	*/
	public function testAddFlash()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'flash'   => [
					'width'  => 123,
					'height' => 456,
					'src'    => 'foo'
				]
			]
		);

		$this->assertEquals(
			'<object type="application/x-shockwave-flash" typemustmatch="" width="123" height="456" data="foo"><param name="allowFullScreen" value="true"/><embed type="application/x-shockwave-flash" src="foo" width="123" height="456" allowfullscreen=""/></object>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox asConfig() returns false if no site was added
	*/
	public function testAsConfigFalseNoSite()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
		$plugin->add('youtube');

		$config = $plugin->asConfig();

		$this->assertFalse($config);
	}

	/**
	* @testdox asConfig() returns a an array containing a "regexp" element by default, if any site was added
	*/
	public function testAsConfigRegexp()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed');
		$plugin->add('youtube');

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('regexp', $config);
	}

	/**
	* @testdox asConfig() returns false if captureURLs is false
	*/
	public function testAsConfigFalseNoCapture()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
		$plugin->add('youtube');

		$config = $plugin->asConfig();

		$this->assertFalse($config);
	}
}