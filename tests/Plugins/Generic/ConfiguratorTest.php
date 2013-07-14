<?php

namespace s9e\TextFormatter\Tests\Plugins\Generic;

use Exception;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp as RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url as UrlFilter;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\Generic\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Generic\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox add() returns the name of the tag it creates
	*/
	public function testAddReturn()
	{
		$plugin = $this->configurator->plugins->load('Generic');

		$this->assertSame(
			'GC53BB427',
			$plugin->add('/(?<foo>[0-9]+)/', '')
		);
	}

	/**
	* @testdox add() throws an exception if the regexp is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regexp
	*/
	public function testInvalidRegexp()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('invalid', '');
	}

	/**
	* @testdox add() throws an exception on duplicate named subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Duplicate named subpatterns are not allowed
	*/
	public function testDuplicateSubpatterns()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('#(?J)(?<foo>x)(?<foo>z)#', '');
	}

	/**
	* @testdox add() creates a tag to represent the replacement
	*/
	public function testCreatesTag()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<foo>[0-9]+)/', '');

		$this->assertTrue($this->configurator->tags->exists($tagName));
	}

	/**
	* @testdox add() creates an attribute for each named subpattern
	*/
	public function testCreatesAttributes()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<w>[0-9]+),(?<h>[0-9]+)/', '');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue($tag->attributes->exists('w'), "Attribute 'w' does not exist");
		$this->assertTrue($tag->attributes->exists('h'), "Attribute 'h' does not exist");
	}

	/**
	* @testdox add() creates an attribute for each numeric subpattern in use
	*/
	public function testCreatesAttributesForSubpatternsInUse()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$1,$2');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue($tag->attributes->exists('_1'), "Attribute '_1' does not exist");
		$this->assertTrue($tag->attributes->exists('_2'), "Attribute '_2' does not exist");
	}

	/**
	* @testdox add() creates a #regexp filter for each attribute created
	*/
	public function testCreatesAttributesWithFilter()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<w>[0-9]+),(?<h>[0-9]+)/', '');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue(
			$tag->attributes->get('w')->filterChain->contains(
				new RegexpFilter('/^(?<w>[0-9]+)$/D')
			)
		);

		$this->assertTrue(
			$tag->attributes->get('h')->filterChain->contains(
				new RegexpFilter('/^(?<h>[0-9]+)$/D')
			)
		);
	}

	/**
	* @testdox add() appends a #url filter to attributes that are used as a URL
	*/
	public function testCreatesAttributesWithUrlFilter()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'/<([^>]+)>/',
			'<a href="$1">$1</a>'
		);

		$tag = $this->configurator->tags->get($tagName);

		$this->assertTrue(
			$tag->attributes->get('_1')->filterChain->contains(
				new UrlFilter
			)
		);
	}

	/**
	* @testdox add() replaces numeric references in the template with the corresponding attribute value
	*/
	public function testNumericReferencesTemplate()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$1,$2');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,<xsl:value-of select="@_2"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() replaces numeric references pointing to named subpatterns in the template with the corresponding attribute value
	*/
	public function testNumericReferencesFromNamedSubpatternsTemplate()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/(?<w>[0-9]+),(?<h>[0-9]+)/', '$1,$2');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@w"/>,<xsl:value-of select="@h"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() alters the regexp to give a name to unnamed subpatterns used in the template
	*/
	public function testAlterRegexpToNameSubpatterns()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$1,$2');

		$config   = $plugin->asConfig();
		$generics = $config['generics']->get();

		$this->assertSame(
			'/(?<_1>[0-9]+),(?<_2>[0-9]+)/',
			$generics[0][1]
		);
	}

	/**
	* @testdox add() does not give a name to unnamed subpatterns that are not used in the template
	*/
	public function testDoesNotNameUnusedSubpatterns()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$2');

		$config   = $plugin->asConfig();
		$generics = $config['generics']->get();

		$this->assertSame(
			'/([0-9]+),(?<_2>[0-9]+)/',
			$generics[0][1]
		);
	}

	/**
	* @testdox add() identifies $1 as a numeric reference in the template
	*/
	public function testNumericReferenceDollar()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$1,$2');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,<xsl:value-of select="@_2"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() identifies \1 as a numeric reference in the template
	*/
	public function testNumericReferenceBackslash()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '\\1,\\2');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,<xsl:value-of select="@_2"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() identifies ${1} as a numeric reference in the template
	*/
	public function testNumericReferenceBraces()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '${1},${2}');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,<xsl:value-of select="@_2"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() interprets \\1 in the template as a literal \1
	*/
	public function testEscapedNumericReference()
	{
		if (PCRE_VERSION < 7.2)
		{
			$this->markTestSkipped('Requires PCRE 7.2');
		}

		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '\\1,\\\\1');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,\\1',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() interprets \\\1 in the template as a literal backslashes followed by a numeric reference
	*/
	public function testEscapedBackslashFollowedByNumericReference()
	{
		if (PCRE_VERSION < 7.2)
		{
			$this->markTestSkipped('Requires PCRE 7.2');
		}

		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '\\\\\\1');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'\\<xsl:value-of select="@_1"/>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() interprets \$1 in the template as a literal $1
	*/
	public function testEscapedDollar()
	{
		if (PCRE_VERSION < 7.2)
		{
			$this->markTestSkipped('Requires PCRE 7.2');
		}

		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/([0-9]+),([0-9]+)/', '$1,\\$1');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<xsl:value-of select="@_1"/>,$1',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() replaces $0 with the whole match
	*/
	public function testNumericReferenceZeroWholeMatch()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add('/@(\\w+)/', '<a href="https://twitter.com/$1">$0</a>');

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<a href="https://twitter.com/{@_1}"><xsl:value-of select="."/></a>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() interprets a (.*?) capture used in template's text as a passthrough
	*/
	public function testPassthrough()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'/\\*(.*?)\\*/i',
			'<em>$1</em>'
		);

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<em><xsl:apply-templates/></em>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox add() interprets a (.*?) capture used in template's text as a passthrough
	*/
	public function testPassthrough2()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
			'<a href="$2">$1</a>'
		);

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<a href="{@_2}"><xsl:apply-templates/></a>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox A passthrough capture used in an attribute does not include the start/end tags
	*/
	public function testPassthroughInAttribute()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'#<(.*)>#',
			'<b title="$1">$1</a>'
		);

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'<b title="{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}"><xsl:apply-templates/></b>',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox A capture used as a URL can also be used as a passthrough, in which case it will used the filtered attribute when used in an attribute, and the normal passthrough when used in text
	* depends testCreatesAttributesWithUrlFilter
	*/
	public function testUrlCapturePassthrough()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'/<(.*?)>/',
			'<a href="$1">$1</a>'
		);

		$this->assertEquals(
			'<a href="{@_1}"><xsl:apply-templates/></a>',
			$this->configurator->tags[$tagName]->defaultTemplate
		);
	}

	/**
	* @testdox Captures from non-existent subpattern are removed from the template
	*/
	public function testNonExistentCaptures()
	{
		$plugin  = $this->configurator->plugins->load('Generic');
		$tagName = $plugin->add(
			'#[0-9]+#i',
			'x$1y'
		);

		$tag = $this->configurator->tags->get($tagName);

		$this->assertEquals(
			'xy',
			$tag->defaultTemplate
		);
	}

	/**
	* @testdox An error occuring during add() does not leave a half-configured tag in the configurator's collection
	*/
	public function testErrorDuringAddDoesNotLeadToInconsistencies()
	{
		$plugin = $this->configurator->plugins->load('Generic');

		try
		{
			$plugin->add('#(?J)(?<foo>x)(?<foo>z)#', '');
		}
		catch (Exception $e)
		{
		}

		$this->assertSame(0, count($this->configurator->tags));
	}

	/**
	* @testdox add() normalizes the tag's template
	*/
	public function testNormalize()
	{
		$tagName = $this->configurator->Generic->add('#^---+$#m', '<xsl:element name="hr"/>');

		$this->assertEquals(
			'<hr/>',
			$this->configurator->tags[$tagName]->defaultTemplate
		);
	}

	/**
	* @testdox add() checks the safeness of the tag
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	*/
	public function testUnsafe()
	{
		$this->configurator->plugins->load('Generic')->add('#<(.*)>#', '<script>$1</script>');
	}

	/**
	* @testdox add() checks the safeness of the tag before adding it to the configurator's collection
	*/
	public function testUnsafeBeforeAdd()
	{
		try
		{
			$this->configurator->plugins->load('Generic')->add('#<(.*)>#', '<script>$1</script>');
		}
		catch (Exception $e)
		{
		}

		$this->assertSame(0, count($this->configurator->tags));
	}

	/**
	* @testdox asConfig() returns FALSE if no replacements were set
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox asConfig() returns the regexps in a "generics" array where each element is in the form [<tagName>,<regexp>,<passthrough index>]
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('/(?<foo>[0-9]+)/', '');
		$plugin->add('/(?<bar>[a-z]+)/', '');

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			[
				'generics' => [
					['GC53BB427', '/(?<foo>[0-9]+)/', 0],
					['GDCEA6E9C', '/(?<bar>[a-z]+)/', 0]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() creates a JavaScript variant of generics
	*/
	public function testAsConfigVariant()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('/(?<foo>[0-9]+)/', '');
		$plugin->add('/(?<bar>[a-z]+)/', '');

		$config = $plugin->asConfig();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['generics']
		);

		$this->assertTrue($config['generics']->has('JS'));
	}

	/**
	* @testdox asConfig() creates JavaScript variants that contain a RegExp object instead of a regexp string, plus a map of named subpatterns
	*/
	public function testAsConfigVariantContent()
	{
		$plugin = $this->configurator->plugins->load('Generic');
		$plugin->add('/(?<foo>[0-9]+)/', '');

		$regexp = new RegExp('([0-9]+)', 'g');
		$regexp->map = ['', 'foo'];

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertEquals(
			[['GC53BB427', $regexp, 0, $regexp->map]],
			$config['generics']
		);
	}
}