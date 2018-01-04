<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed;

use Exception;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox finalize() registers MediaEmbed.hosts and MediaEmbed.sites as variables
	*/
	public function testRegistersVars()
	{
		$this->configurator->MediaEmbed->finalize();

		$this->assertArrayHasKey('MediaEmbed.hosts', $this->configurator->registeredVars);
		$this->assertArrayHasKey('MediaEmbed.sites', $this->configurator->registeredVars);
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
	* @testdox Does not create a [media] BBCode if createMediaBBCode is false
	*/
	public function testNoMediaBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed', ['createMediaBBCode' => false]);
		$this->assertFalse(isset($this->configurator->BBCodes['MEDIA']));
	}

	/**
	* @testdox add('youtube') does not create a [youtube] BBCode by default
	*/
	public function testSiteBBCodeDefault()
	{
		$this->configurator->MediaEmbed->add('youtube');
		$this->assertFalse(isset($this->configurator->BBCodes['YOUTUBE']));
	}

	/**
	* @testdox add('inexistent') throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Media site 'inexistent' does not exist
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
					'host'    => 'youtube.com',
					'extract' => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
					'iframe'  => [
						'width'  => 560,
						'height' => 315,
						'src'    => '//localhost'
					]
				]
			)
		);
	}

	/**
	* @testdox add() creates an attribute for every named subpattern in extract
	*/
	public function testAddAttributesExtract()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'    => 'youtube.com',
				'extract' => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertTrue($tag->attributes->exists('path'));
		$this->assertTrue($tag->attributes->exists('id'));
	}

	/**
	* @testdox add() creates an attribute for every named subpattern in scrape/extract
	*/
	public function testAddAttributesScrape()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					'match'   => '#/\\d+#',
					'extract' => "#/(?'vid'(?'id'\\d+))#"
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertTrue($tag->attributes->exists('id'));
		$this->assertTrue($tag->attributes->exists('vid'));
	}

	/**
	* @testdox add() accepts multiple "host" elements
	*/
	public function testAddMultipleHost()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'    => ['youtube.com', 'youtu.be'],
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);
		$this->configurator->finalize();
		$this->assertEquals(
			['youtube.com' => 'youtube', 'youtu.be' => 'youtube'],
			iterator_to_array($this->configurator->registeredVars['MediaEmbed.hosts'])
		);
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
					'width'  => 800,
					'height' => 450,
					'src'    => 'foo'
				]
			]
		);

		$this->assertEquals(
			'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:800px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></span>',
			$tag->template
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
					'width'  => 800,
					'height' => 450,
					'src'    => 'foo'
				]
			]
		);

		$this->assertEquals(
			'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:800px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><object data="foo" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"/></object></span></span>',
			$tag->template
		);
	}

	/**
	* @testdox add() sets an empty template if none is specified
	*/
	public function testAddNoTemplate()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!"
			]
		);

		$this->assertEquals('', $tag->template);
	}

	/**
	* @testdox add() handles multiple-choice templates
	*/
	public function testAddChoose()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'   => 'foo.invalid',
				'choose' => [
					'when'      => [
						'test'   => '@foo',
						'iframe' => ['width' => '560', 'height' => '315', 'src' => 'foo']
					],
					'otherwise' => [
						'iframe' => ['width' => '800', 'height' => '450', 'src' => 'bar']
					]
				]
			]
		);

		$this->assertEquals(
			'<span data-s9e-mediaembed="foo"><xsl:choose><xsl:when test="@foo"><xsl:attribute name="style">display:inline-block;width:100%;max-width:560px</xsl:attribute><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></xsl:when><xsl:otherwise><xsl:attribute name="style">display:inline-block;width:100%;max-width:800px</xsl:attribute><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="bar" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></xsl:otherwise></xsl:choose></span>',
			$tag->template
		);
	}

	/**
	* @testdox add() handles multiple-choice templates with more than 2 branches
	*/
	public function testAddChooseMultiple()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'   => 'foo.invalid',
				'choose' => [
					'when'      => [
						[
							'test'   => '@foo',
							'iframe' => ['width' => '100', 'height' => '100', 'src' => 'foo']
						],
						[
							'test'   => '@bar',
							'iframe' => ['width' => '200', 'height' => '200', 'src' => 'bar']
						],
					],
					'otherwise' => [
						'iframe' => ['width' => '300', 'height' => '300', 'src' => 'baz']
					]
				]
			]
		);

		$this->assertEquals(
			'<span data-s9e-mediaembed="foo"><xsl:choose><xsl:when test="@foo"><xsl:attribute name="style">display:inline-block;width:100%;max-width:100px</xsl:attribute><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></xsl:when><xsl:when test="@bar"><xsl:attribute name="style">display:inline-block;width:100%;max-width:200px</xsl:attribute><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="bar" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></xsl:when><xsl:otherwise><xsl:attribute name="style">display:inline-block;width:100%;max-width:300px</xsl:attribute><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="baz" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></xsl:otherwise></xsl:choose></span>',
			$tag->template
		);
	}

	/**
	* @testdox add() checks the tag's safety before adding it
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'id' is not properly sanitized
	*/
	public function testAddUnsafe()
	{
		try
		{
			$this->configurator->MediaEmbed->add(
				'youtube',
				[
					'host'    => 'youtube.com',
					'extract' => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
					'iframe'  => [
						'width'  => 560,
						'height' => 315,
						'src'    => '//localhost',
						'onload' => '{@id}'
					]
				]
			);
		}
		catch (Exception $e)
		{
			if (isset($this->configurator->tags['YOUTUBE']))
			{
				$this->fail('A tag was created');
			}

			throw $e;
		}
	}

	/**
	* @testdox add() uses explicit attribute declarations
	*/
	public function testAddExplicitAttributes()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'iframe'     => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				],
				'attributes' => [
					'id' => ['required' => false],
					'xx' => ['filterChain' => ['#number']]
				]
			]
		);

		$this->assertFalse($tag->attributes['id']->required);
		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			[$this->configurator->attributeFilters['#number']],
			iterator_to_array($tag->attributes['xx']->filterChain)
		);
	}

	/**
	* @testdox add() throws a RuntimeException if a filter is not allowed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Filter 'eval' is not allowed in media sites
	*/
	public function testDisallowedFilter()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			[
				'host'      => 'youtube.com',
				'extract'   => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				],
				'attributes' => [
					'id' => ['required' => false],
					'xx' => ['filterChain' => ['eval']]
				]
			]
		);
	}

	/**
	* @testdox add() processes the optional defaultValue in attribute declarations
	*/
	public function testAddDefaultValue()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'localhost',
			[
				'host'      => 'localhost',
				'extract'   => "!localhost/(?'id'\\d+)!",
				'template'   => '',
				'attributes' => [
					'id' => ['required' => false],
					'height' => ['defaultValue' => 123]
				]
			]
		);

		$this->assertTrue(isset($tag->attributes['height']->defaultValue));
		$this->assertEquals(123, $tag->attributes['height']->defaultValue);
	}

	/**
	* @testdox add() throws an InvalidArgumentException if the site ID is not entirely made of alphanumeric characters
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid site ID
	*/
	public function testAddInvalidId()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'../youtube',
			[
				'host'      => 'youtube.com',
				'extract'   => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!'
			]
		);
	}

	/**
	* @testdox asConfig() returns NULL if no site was added
	*/
	public function testAsConfigNullNoSite()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed');

		$config = $plugin->asConfig();

		$this->assertNull($config);
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
	* @testdox asConfig() creates a regexp if a site has a "host"
	*/
	public function testAsConfigRegexpHost()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => 'example.org',
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			]
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'/\\bhttps?:\\/\\/[^["\'\\s]+/Si',
			$config['regexp']
		);
	}

	/**
	* @testdox asConfig() returns '://' as quickMatch
	*/
	public function testAsConfigQuickMatchHost()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => 'example.org',
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			]
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame('://', $config['quickMatch']);
	}
}