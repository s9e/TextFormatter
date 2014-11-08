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
		$this->configurator->plugins->load('MediaEmbed', array('createBBCodes' => false));
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
	* @testdox add('youtube') does not create a [youtube] BBCode if createBBCodes is false
	*/
	public function testNoSiteBBCode()
	{
		$this->configurator->plugins->load('MediaEmbed', array('createBBCodes' => false))->add('youtube');
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
				array(
					'host'     => 'youtube.com',
					'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
					'template' => 'YouTube!'
				)
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
			array(
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			)
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					'match'   => '#/\\d+#',
					'extract' => "#/(?'vid'(?'id'\\d+))#"
				),
				'template' => 'Example!'
			)
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					'url'     => 'http://example.org/{@id}',
					'match'   => "#/(?'id'\\d+)#",
					'extract' => "#/(?'vid'\\d+)#"
				),
				'template' => 'Example!'
			)
		);

		$this->assertEquals(
			array(
				'scrapeConfig' => array(
					array(
						"#/(?'id'\d+)#",
						"#/(?'vid'\d+)#",
						array('vid'),
						'http://example.org/{@id}'
					)
				)
			),
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
			array(
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			)
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
			array(
				'host'     => 'youtube.com',
				'extract'  => "!(?'url'youtube\\.com/.+)!",
				'template' => 'YouTube!'
			)
		);
	}

	/**
	* @testdox The "url" attribute keeps its #url filter instead of a #regexp filter even if it's used in a capture
	*/
	public function testAddCaptureUrlFilter()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'example',
			array(
				'host'     => 'youtube.com',
				'extract'  => "!(?'url'youtube\\.com/.+)!",
				'template' => 'YouTube!'
			)
		);

		$this->assertEquals(
			array($this->configurator->attributeFilters['#url']),
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
			array(
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			)
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
			array(
				'host'     => 'youtube.com',
				'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template' => 'YouTube!'
			)
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
			array(
				'host'     => 'youtube.com',
				'extract'  => $r1,
				'template' => 'YouTube!'
			)
		);

		$expected = array(
			array('url', new AttributePreprocessor($r1)),
			array('url', new AttributePreprocessor("!^(?'id'[-0-9A-Z_a-z]+)\$!D"))
		);
		$actual = array();
		foreach ($tag->attributePreprocessors as $k => $v)
		{
			$actual[] = array($k, $v);
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
			array(
				'host'     => 'youtube.com',
				'extract'  => array($r1, $r2),
				'template' => 'YouTube!'
			)
		);

		$expected = array(
			array('url', new AttributePreprocessor($r1)),
			array('url', new AttributePreprocessor($r2)),
			array('url', new AttributePreprocessor("!^(?'id'[-0-9A-Z_a-z]+)\$!D"))
		);
		$actual = array();
		foreach ($tag->attributePreprocessors as $k => $v)
		{
			$actual[] = array($k, $v);
		}

		$this->assertEquals($expected, $actual);
	}

	/**
	* @testdox add() accepts multiple "host" elements
	*/
	public function testAddMultipleHost()
	{
		$hosts = array('youtube.com', 'youtu.be');

		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'     => $hosts,
				'extract'  => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'template' => 'YouTube!'
			)
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					array(
						'match'   => '#/v/\\d+#',
						'extract' => "#id=(?'id'\\d+)#"
					),
					array(
						'match'   => '#/V/\\d+#',
						'extract' => "#id=(?'id'\\d+)#"
					)
				),
				'template' => 'Example!'
			)
		);

		$this->assertEquals(
			array(
				'scrapeConfig' => array(
					array('#/v/\d+#', "#id=(?'id'\d+)#", array('id')),
					array('#/V/\d+#', "#id=(?'id'\d+)#", array('id'))
				)
			),
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					array(
						'match'   => array('#/v/\\d+#', '#/V/\\d+#'),
						'extract' => "#id=(?'id'\\d+)#"
					)
				),
				'template' => 'Example!'
			)
		);

		$this->assertEquals(
			array(
				'scrapeConfig' => array(
					array(
						array('#/v/\d+#', '#/V/\d+#'),
						"#id=(?'id'\d+)#",
						array('id')
					)
				)
			),
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					array(
						'extract' => "#id=(?'id'\\d+)#"
					)
				),
				'template' => 'Example!'
			)
		);

		$this->assertEquals(
			array(
				'scrapeConfig' => array(
					array(
						'//',
						"#id=(?'id'\d+)#",
						array('id')
					)
				)
			),
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
			array(
				'host'     => 'example.org',
				'scrape'   => array(
					array(
						'match'   => '#/v/\\d+#',
						'extract' => array(
							"#id=(?'id'\\d+)#",
							"#xd=(?'xd'\\d+)#"
						)
					)
				),
				'template' => 'Example!'
			)
		);

		$this->assertEquals(
			array(
				'scrapeConfig' => array(
					array(
						'#/v/\d+#',
						array(
							"#id=(?'id'\d+)#",
							"#xd=(?'xd'\d+)#"
						),
						array('id', 'xd')
					)
				)
			),
			$tag->filterChain[1]->getVars()
		);
	}

	/**
	* @testdox add() sets the tag's default template to the "template" element if available
	*/
	public function testAddTemplate()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'     => 'youtu.be',
				'extract'  => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'template' => 'YouTube!'
			)
		);

		$this->assertEquals('YouTube!', $tag->template);
	}

	/**
	* @testdox add() sets the tag's default template to the iframe defined in the "iframe" element if available
	*/
	public function testAddIframe()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => array(
					'width'  => 123,
					'height' => 456,
					'src'    => 'foo'
				)
			)
		);

		$this->assertEquals(
			'<iframe width="123" height="456" src="foo" allowfullscreen="" frameborder="0" scrolling="no"/>',
			$tag->template
		);
	}

	/**
	* @testdox add() treats iframe attributes as attribute value templates
	*/
	public function testAddIframeDynamic()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => array(
					'width'  => '{@width}',
					'height' => '{@height}',
					'src'    => 'foo'
				)
			)
		);

		$this->assertEquals(
			'<iframe width="{@width}" height="{@height}" src="foo" allowfullscreen="" frameborder="0" scrolling="no"/>',
			$tag->template
		);
	}

	/**
	* @testdox Extra attributes can be added to an iframe
	*/
	public function testAddIframeAttributes()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'iframe'  => array(
					'width'  => '{@width}',
					'height' => '{@height}',
					'src'    => 'foo',
					'onload' => "this.foo='bar'"
				)
			)
		);

		$this->assertEquals(
			'<iframe width="{@width}" height="{@height}" src="foo" onload="this.foo=\'bar\'" allowfullscreen="" frameborder="0" scrolling="no"/>',
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
			array(
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'flash'   => array(
					'width'  => 123,
					'height' => 456,
					'src'    => 'foo'
				)
			)
		);

		$this->assertEquals(
			'<object type="application/x-shockwave-flash" typemustmatch="" width="123" height="456" data="foo"><param name="allowfullscreen" value="true"/><embed type="application/x-shockwave-flash" src="foo" width="123" height="456" allowfullscreen=""/></object>',
			$tag->template
		);
	}

	/**
	* @testdox add() treats flash attributes as attribute value templates
	*/
	public function testAddFlashDynamic()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'    => 'youtu.be',
				'extract' => "!youtu\\.be/(?'id'[-0-9A-Z_a-z]+)!",
				'flash'   => array(
					'width'  => '{@width}',
					'height' => '{@height}',
					'src'    => 'foo'
				)
			)
		);

		$this->assertEquals(
			'<object type="application/x-shockwave-flash" typemustmatch="" width="{@width}" height="{@height}" data="foo"><param name="allowfullscreen" value="true"/><embed type="application/x-shockwave-flash" src="foo" width="{@width}" height="{@height}" allowfullscreen=""/></object>',
			$tag->template
		);
	}

	/**
	* @testdox add() sets flashvars if applicable
	*/
	public function testAddFlashVars()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'foo',
			array(
				'host'    => 'foo.invalid',
				'extract' => "!(?'id'[-0-9A-Z_a-z]+)!",
				'flash'   => array(
					'width'     => 123,
					'height'    => 456,
					'src'       => 'foo',
					'flashvars' => 'foo=1&bar=2'
				)
			)
		);

		$this->assertEquals(
			'<object type="application/x-shockwave-flash" typemustmatch="" width="123" height="456" data="foo"><param name="allowfullscreen" value="true"/><param name="flashvars" value="foo=1&amp;bar=2"/><embed type="application/x-shockwave-flash" src="foo" width="123" height="456" allowfullscreen="" flashvars="foo=1&amp;bar=2"/></object>',
			$tag->template
		);
	}

	/**
	* @testdox add() checks the tag's safety before adding it
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage disable-output-escaping
	*/
	public function testAddUnsafe()
	{
		try
		{
			$this->configurator->MediaEmbed->add(
				'youtube',
				array(
					'host'     => 'youtube.com',
					'extract'  => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
					'template' => '<xsl:value-of select="." disable-output-escaping="yes"/>'
				)
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
			array(
				'host'     => 'example.com',
				'extract'  => array(
					"!example\\.com/(?<foo>\\d+)!",
					"!example\\.com/(?<bar>\\D+)!"
				),
				'template' => 'foo'
			)
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
			array(
				'host'     => 'example.com',
				'extract'  => array(
					"!example\\.com/(?<id>\\d+)!",
					"!example\\.com/(?<bar>\\D+)!"
				),
				'template' => 'foo'
			)
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
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!',
				'attributes' => array(
					'id' => array('required' => false),
					'xx' => array('type' => 'number')
				)
			)
		);

		$this->assertFalse($tag->attributes['id']->required);
		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			array($this->configurator->attributeFilters['#number']),
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
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!',
				'attributes' => array(
					'id' => array('required' => false),
					'xx' => array('type' => 'number', 'preFilter' => 'hexdec')
				)
			)
		);

		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			array(
				$this->configurator->attributeFilters['hexdec'],
				$this->configurator->attributeFilters['#number']
			),
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
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!',
				'attributes' => array(
					'id' => array('required' => false),
					'xx' => array('type' => 'number', 'preFilter' => 'eval')
				)
			)
		);
	}

	/**
	* @testdox add() processes the optional postFilter in attribute declarations
	*/
	public function testAddPostFilter()
	{
		$tag = $this->configurator->MediaEmbed->add(
			'youtube',
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!',
				'attributes' => array(
					'id' => array('required' => false),
					'xx' => array('type' => 'number', 'postFilter' => 'hexdec')
				)
			)
		);

		$this->assertTrue(isset($tag->attributes['xx']));
		$this->assertEquals(
			array(
				$this->configurator->attributeFilters['#number'],
				$this->configurator->attributeFilters['hexdec']
			),
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
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!',
				'attributes' => array(
					'id' => array('required' => false),
					'xx' => array('type' => 'number', 'postFilter' => 'eval')
				)
			)
		);
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
			array(
				'host'       => 'youtube.com',
				'extract'    => "!youtube\\.com/(?<path>v/(?'id'[-0-9A-Z_a-z]+))!",
				'template'   => 'YouTube!'
			)
		);
	}

	/**
	* @testdox add() throws a RuntimeException if the default XML config is malformed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Invalid XML
	*/
	public function testAddInvalidXML()
	{
		$id = uniqid('x');
		$filepath = sys_get_temp_dir() . '/' . $id . '.xml';
		self::$tmpFiles[] = $filepath;

		$this->configurator->MediaEmbed->sitesDir = sys_get_temp_dir();
		file_put_contents($filepath, '<invalid>');

		$tag = @$this->configurator->MediaEmbed->add($id);
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
	* @testdox asConfig() returns false if no site was added
	*/
	public function testAsConfigFalseNoSite()
	{
		$plugin = $this->configurator->plugins->load('MediaEmbed');

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
		$plugin = $this->configurator->plugins->load('MediaEmbed', array('captureURLs' => false));
		$plugin->add('youtube');

		$config = $plugin->asConfig();

		$this->assertFalse($config);
	}

	/**
	* @testdox asConfig() creates a regexp if a site has a "host"
	*/
	public function testAsConfigRegexpHost()
	{
		$this->configurator->MediaEmbed->add(
			'foo',
			array(
				'host'     => 'example.org',
				'extract'  => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			)
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'#\\bhttps?://(?:[-.\\w]+\\.)?example\.org/[^["\'\\s]+(?!\\S)#S',
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
			array(
				'scheme'   => 'bar',
				'extract'  => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			)
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'#\\bbar:[^["\'\\s]+(?!\\S)#S',
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
			array(
				'host'     => array('example.com', 'example.org'),
				'scheme'   => array('bar', 'baz'),
				'extract'  => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			)
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame(
			'#\\b(?>ba[rz]:|https?://(?:[-.\\w]+\\.)?example\\.(?>com|org)/)[^["\'\\s]+(?!\\S)#S',
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
			array(
				'scheme'   => array('bar', 'baz'),
				'extract'  => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			)
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
			array(
				'host'     => 'example.org',
				'extract'  => "!(?'id'[-0-9A-Z_a-z]+)!",
				'template' => ''
			)
		);

		$config = $this->configurator->MediaEmbed->asConfig();

		$this->assertSame('://', $config['quickMatch']);
	}
}