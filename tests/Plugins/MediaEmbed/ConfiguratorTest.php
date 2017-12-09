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
	* @testdox $configurator->MediaEmbed->captureURLs is accessible
	*/
	public function testCaptureURLsIsPublic()
	{
		$this->configurator->MediaEmbed->captureURLs = false;
		$this->assertFalse($this->configurator->MediaEmbed->captureURLs);
		$this->configurator->MediaEmbed->captureURLs = true;
		$this->assertTrue($this->configurator->MediaEmbed->captureURLs);
	}

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
	* @testdox add('youtube') creates a [youtube] BBCode if createIndividualBBCodes is True
	*/
	public function testSiteBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed', ['createIndividualBBCodes' => true])->add('youtube');
		$this->assertTrue(isset($this->configurator->BBCodes['YOUTUBE']));
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
	* @testdox add() saves the "url" attribute of a scrape if applicable
	*/
	public function testAddScrapeUrl()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					'url'     => 'http://example.org/{@id}',
					'match'   => "#/(?'id'\\d+)#",
					'extract' => "#/(?'vid'\\d+)#"
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[
				'scrapeConfig' => [
					[
						["#/(?'id'\\d+)#"],
						["#/(?'vid'\\d+)#"],
						['vid'],
						'http://example.org/{@id}'
					]
				]
			],
			$tag->filterChain[1]->getVars()
		);
	}

	/**
	* @testdox add() creates an optional "url" attribute
	*/
	public function testAddOptionalUrl()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
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

		$this->assertTrue($tag->attributes->exists('url'));
		$this->assertFalse($tag->attributes['url']->required);
	}

	/**
	* @testdox Extract regexps can contain an "url" capture
	*/
	public function testAddCaptureUrl()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'youtube.com',
				'extract' => "!(?'url'youtube\\.com/.+)!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);
	}

	/**
	* @testdox The "url" attribute keeps its #url filter instead of a #regexp filter even if it's used in a capture
	*/
	public function testAddCaptureUrlFilter()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'youtube.com',
				'extract' => "!(?'url'youtube\\.com/.+)!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[$this->configurator->attributeFilters['#url']],
			iterator_to_array($tag->attributes['url']->filterChain)
		);
	}

	/**
	* @testdox add() marks the "id" attribute as non-optional if present
	*/
	public function testAddIdRequired()
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
				'host'    => 'youtube.com',
				'extract' => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
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
				'host'    => 'youtube.com',
				'extract' => $r1,
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
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
				'host'    => 'youtube.com',
				'extract' => [$r1, $r2],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
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
				'host'    => $hosts,
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			$hosts,
			$this->configurator->registeredVars['mediasites']['youtube']['host']
		);
	}

	/**
	* @testdox add() accepts multiple "scrape" elements
	*/
	public function testAddMultipleScrape()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					[
						'match'   => '#/v/\\d+#',
						'extract' => "#id=(?'id'\\d+)#"
					],
					[
						'match'   => '#/V/\\d+#',
						'extract' => "#id=(?'id'\\d+)#"
					]
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[
				'scrapeConfig' => [
					[['#/v/\d+#'], ["#id=(?'id'\d+)#"], ['id']],
					[['#/V/\d+#'], ["#id=(?'id'\d+)#"], ['id']]
				]
			],
			$tag->filterChain[1]->getVars()
		);
	}

	/**
	* @testdox add() accepts multiple "match" elements in "scrape"
	*/
	public function testAddMultipleMatchScrape()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					[
						'match'   => ['#/v/\\d+#', '#/V/\\d+#'],
						'extract' => "#id=(?'id'\\d+)#"
					]
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[
				'scrapeConfig' => [
					[
						['#/v/\d+#', '#/V/\d+#'],
						["#id=(?'id'\d+)#"],
						['id']
					]
				]
			],
			$tag->filterChain[1]->getVars()
		);
	}

	/**
	* @testdox add() accepts zero "match" elements in "scrape"
	*/
	public function testAddZeroMatchScrape()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					[
						'extract' => "#id=(?'id'\\d+)#"
					]
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[
				'scrapeConfig' => [
					[
						['//'],
						["#id=(?'id'\d+)#"],
						['id']
					]
				]
			],
			$tag->filterChain[1]->getVars()
		);
	}

	/**
	* @testdox add() accepts multiple "extract" elements in "scrape"
	*/
	public function testAddMultipleExtractScrape()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			[
				'host'    => 'example.org',
				'scrape'   => [
					[
						'match'   => '#/v/\\d+#',
						'extract' => [
							"#id=(?'id'\\d+)#",
							"#xd=(?'xd'\\d+)#"
						]
					]
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$this->assertEquals(
			[
				'scrapeConfig' => [
					[
						['#/v/\d+#'],
						[
							"#id=(?'id'\d+)#",
							"#xd=(?'xd'\d+)#"
						],
						['id', 'xd']
					]
				]
			],
			$tag->filterChain[1]->getVars()
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
	* @testdox add() appends Parser::hasNonDefaultAttribute() to the filter chain if the tag has no required attributes
	*/
	public function testAddFilterIfNoRequired()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => 'example.com',
				'extract' => [
					"!example\\.com/(?<foo>\\d+)!",
					"!example\\.com/(?<bar>\\D+)!"
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$expected = 's9e\\TextFormatter\\Plugins\\MediaEmbed\\Parser::hasNonDefaultAttribute';
		foreach ($tag->filterChain as $filter)
		{
			if ($filter->getCallback() === $expected)
			{
				return;
			}
		}

		$this->fail('Could not find the expected callback');
	}

	/**
	* @testdox add() does not test for non-default attributes if the tag has a required attribute
	*/
	public function testAddNoFilterIfRequired()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => 'example.com',
				'extract' => [
					"!example\\.com/(?<id>\\d+)!",
					"!example\\.com/(?<bar>\\D+)!"
				],
				'iframe'  => [
					'width'  => 560,
					'height' => 315,
					'src'    => '//localhost'
				]
			]
		);

		$callback = 's9e\\TextFormatter\\Plugins\\MediaEmbed\\Parser::hasNonDefaultAttribute';
		foreach ($tag->filterChain as $filter)
		{
			if ($filter->getCallback() === $callback)
			{
				$this->fail('The filter chain should not contain ' . $callback);
			}
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
					'xx' => ['type' => 'number']
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
	* @testdox add() processes the optional preFilter in attribute declarations
	*/
	public function testAddPreFilter()
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
					'xx' => ['type' => 'number', 'preFilter' => 'hexdec']
				]
			]
		);

		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			[
				$this->configurator->attributeFilters['hexdec'],
				$this->configurator->attributeFilters['#number']
			],
			iterator_to_array($tag->attributes['xx']->filterChain)
		);
	}

	/**
	* @testdox add() throws a RuntimeException if the optional preFilter is not allowed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Filter 'eval' is not allowed
	*/
	public function testAddPreFilterInvalid()
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
					'xx' => ['type' => 'number', 'preFilter' => 'eval']
				]
			]
		);
	}

	/**
	* @testdox add() processes the optional postFilter in attribute declarations
	*/
	public function testAddPostFilter()
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
					'xx' => ['type' => 'number', 'postFilter' => 'hexdec']
				]
			]
		);

		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			[
				$this->configurator->attributeFilters['#number'],
				$this->configurator->attributeFilters['hexdec']
			],
			iterator_to_array($tag->attributes['xx']->filterChain)
		);
	}

	/**
	* @testdox add() throws a RuntimeException if the optional postFilter is not allowed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Filter 'eval' is not allowed
	*/
	public function testAddPostFilterInvalid()
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
					'xx' => ['type' => 'number', 'postFilter' => 'eval']
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
					'height' => ['type' => 'number', 'defaultValue' => 123]
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
	* @testdox appendTemplate() sets a template to be appended to media sites' templates
	*/
	public function testAppend()
	{
		$template = '<a href="{@url}"><xsl:value-of select="@url"/></a>';

		$this->configurator->MediaEmbed->appendTemplate($template);
		$this->configurator->MediaEmbed->add('youtube');

		$this->assertContains(
			$template,
			(string) $this->configurator->tags['YOUTUBE']->template
		);
	}

	/**
	* @testdox appendTemplate() accepts HTML
	*/
	public function testAppendHTML()
	{
		$template = '<br><a href="{@url}">Original link</a>';

		$this->configurator->MediaEmbed->appendTemplate($template);
		$this->configurator->MediaEmbed->add('youtube');

		$this->assertContains(
			'<br/><a href="{@url}">Original link</a>',
			(string) $this->configurator->tags['YOUTUBE']->template
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
	* @testdox asConfig() returns NULL if captureURLs is false
	*/
	public function testAsConfigNullNoCapture()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
		$plugin->add('youtube');

		$config = $plugin->asConfig();

		$this->assertNull($config);
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
	* @testdox asConfig() creates a regexp if a site has a "scheme"
	*/
	public function testAsConfigRegexpScheme()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'scheme'   => 'bar',
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			]
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'/\\b(?>bar:|https?:\\/\\/)[^["\'\\s]+/Si',
			$config['regexp']
		);
	}

	/**
	* @testdox asConfig() creates a regexp if a site has both "host" and "scheme"
	*/
	public function testAsConfigRegexpBoth()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'host'    => ['example.com', 'example.org'],
				'scheme'   => ['bar', 'baz'],
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			]
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'/\\b(?>ba[rz]:|https?:\\/\\/)[^["\'\\s]+/Si',
			$config['regexp']
		);
	}

	/**
	* @testdox asConfig() returns ':' as quickMatch if it has a site with a "scheme"
	*/
	public function testAsConfigQuickMatchScheme()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			[
				'scheme'   => ['bar', 'baz'],
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			]
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(':', $config['quickMatch']);
	}

	/**
	* @testdox asConfig() returns '://' as quickMatch if it has no site with a "scheme"
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