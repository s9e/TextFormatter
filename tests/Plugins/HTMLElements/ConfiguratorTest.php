<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLElements;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Plugins\HTMLElements\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLElements\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox aliasElement('A', 'url') creates an alias for HTML element "a" to tag "URL"
	*/
	public function testAliasElement()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->aliasElement('A', 'url');

		$pluginConfig = $plugin->asConfig();
		ConfigHelper::filterVariants($pluginConfig);

		$this->assertArrayMatches(
			['aliases' => ['a' => ['' => 'URL']]],
			$pluginConfig
		);
	}

	/**
	* @testdox aliasAttribute('A', 'HREF', 'URL') creates an alias for HTML attribute "href" in HTML element "a" to attribute "url"
	*/
	public function testAliasAttribute()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->aliasAttribute('A', 'HREF', 'URL');

		$pluginConfig = $plugin->asConfig();
		ConfigHelper::filterVariants($pluginConfig);

		$this->assertArrayMatches(
			['aliases' => ['a' => ['href' => 'url']]],
			$pluginConfig
		);
	}

	/**
	* @testdox allowElement('b') creates a tag named 'html:b'
	*/
	public function testCreatesTags()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');

		$this->assertTrue($this->configurator->tags->exists('html:b'));
	}

	/**
	* @testdox allowElement('B') creates a tag named 'html:b'
	*/
	public function testCreatesTagsCaseInsensitive()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('B');

		$this->assertTrue($this->configurator->tags->exists('html:b'));
	}

	/**
	* @testdox allowElement() returns an instance of Tag
	*/
	public function testAllowElementInstance()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Tag',
			$plugin->allowElement('b')
		);
	}

	/**
	* @testdox allowElement() can be called multiple times with the same element
	*/
	public function testAllowElementMultiple()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');

		$this->assertSame(
			$plugin->allowElement('b'),
			$plugin->allowElement('b')
		);
	}

	/**
	* @testdox The prefix can be customized at loading time through the 'prefix' property
	*/
	public function testCustomPrefix()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements', ['prefix' => 'xyz']);
		$plugin->allowElement('b');

		$this->assertTrue($this->configurator->tags->exists('xyz:b'));
	}

	/**
	* @testdox allowElement('script') throws an exception
	* @expectedException RuntimeException unsafe
	*/
	public function testUnsafeElement()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('script');
	}

	/**
	* @testdox allowUnsafeElement('script') allows the 'script' element
	*/
	public function testUnsafeElementAllowed()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowUnsafeElement('script');

		$this->assertTrue($this->configurator->tags->exists('html:script'));
	}

	/**
	* @testdox allowUnsafeElement() returns an instance of Tag
	*/
	public function testAllowUnsafeElementInstance()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Tag',
			$plugin->allowUnsafeElement('script')
		);
	}

	/**
	* @testdox allowAttribute('b', 'title') creates an attribute 'title' on tag 'html:b'
	*/
	public function testCreatesAttributes()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');
		$plugin->allowAttribute('b', 'title');

		$this->assertTrue($this->configurator->tags['html:b']->attributes->exists('title'));
	}

	/**
	* @testdox allowAttribute() returns an instance of Attribute
	*/
	public function testAllowAttributeInstance()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Attribute',
			$plugin->allowAttribute('b', 'title')
		);
	}

	/**
	* @testdox Attributes created by allowAttribute() are considered optional
	*/
	public function testCreatesOptionalAttributes()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');
		$plugin->allowAttribute('b', 'title');

		$this->assertFalse($this->configurator->tags['html:b']->attributes['title']->required);
	}

	/**
	* @testdox Attributes that are known to expect an URL are created with the '#url' filter
	*/
	public function testFilter()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('a');
		$plugin->allowAttribute('a', 'href');

		$this->assertTrue($this->configurator->tags['html:a']->attributes['href']->filterChain->contains(new UrlFilter));
	}

	/**
	* @testdox allowAttribute('b', 'title') throws an exception if 'b' was not explicitly allowed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Element 'b' has not been allowed
	*/
	public function testAttributeOnUnknownElement()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowAttribute('b', 'title');
	}

	/**
	* @testdox allowAttribute('span', 'onmouseover') throws an exception
	* @expectedException RuntimeException unsafe
	*/
	public function testUnsafeAttribute()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('span');
		$plugin->allowAttribute('span', 'onmouseover');
	}

	/**
	* @testdox allowAttribute('span', 'style') throws an exception
	* @expectedException RuntimeException unsafe
	*/
	public function testUnsafeAttribute2()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('span');
		$plugin->allowAttribute('span', 'style');
	}

	/**
	* @testdox allowUnsafeAttribute('span', 'onmouseover') allows the 'onmouseover' attribute on 'span' elements
	*/
	public function testUnsafeAttributeAllowed()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('span');
		$plugin->allowUnsafeAttribute('span', 'onmouseover');

		$this->assertTrue($this->configurator->tags['html:span']->attributes->exists('onmouseover'));
	}

	/**
	* @testdox allowUnsafeAttribute() returns an instance of Attribute
	*/
	public function testAllowUnsafeAttributeInstance()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Attribute',
			$plugin->allowUnsafeAttribute('b', 'onclick')
		);
	}

	/**
	* @testdox allowElement('*invalid*') throws an exception
	* @expectedException InvalidArgumentException invalid
	*/
	public function testInvalidElementName()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('*invalid*');
	}

	/**
	* @testdox allowAttribute('span', 'data-title') allows the 'data-title' attribute on 'span' elements
	*/
	public function testAttributeNameDash()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('span');
		$plugin->allowUnsafeAttribute('span', 'data-title');

		$this->assertTrue($this->configurator->tags['html:span']->attributes->exists('data-title'));
	}

	/**
	* @testdox allowAttribute('b', '*invalid*') throws an exception
	* @expectedException InvalidArgumentException invalid
	*/
	public function testInvalidAttributeName()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');
		$plugin->allowAttribute('b', '*invalid*');
	}

	/**
	* @testdox asConfig() returns NULL if no elements were allowed
	*/
	public function testNullConfig()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$this->assertNull($plugin->asConfig());
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');

		$this->assertArrayHasKey(
			'quickMatch',
			$plugin->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->allowElement('b');

		$this->assertArrayHasKey('regexp', $plugin->asConfig());
	}

	/**
	* @testdox asConfig() preserves aliased elements' keys in a JS variant
	*/
	public function testAsConfigAliasElement()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->aliasElement('A', 'url');

		$pluginConfig = $plugin->asConfig();
		ConfigHelper::filterVariants($pluginConfig, 'JS');

		$this->assertEquals(
			new Dictionary(['a' => new Dictionary(['' => 'URL'])]),
			$pluginConfig['aliases']
		);
	}

	/**
	* @testdox asConfig() preserves aliased attributes' keys in a JS variant
	*/
	public function testAsConfigAliasAttribute()
	{
		$plugin = $this->configurator->plugins->load('HTMLElements');
		$plugin->aliasAttribute('A', 'HREF', 'URL');

		$pluginConfig = $plugin->asConfig();
		ConfigHelper::filterVariants($pluginConfig, 'JS');

		$this->assertEquals(
			new Dictionary(['a' => new Dictionary(['href' => 'url'])]),
			$pluginConfig['aliases']
		);
	}

	/**
	* @testdox getJSHints() returns ['HTMLELEMENTS_HAS_ALIASES' => 0] by default
	*/
	public function testGetJSHintsFalse()
	{
		$plugin = $this->configurator->HTMLElements;
		$plugin->allowElement('A');
		$this->assertSame(
			['HTMLELEMENTS_HAS_ALIASES' => 0],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['HTMLELEMENTS_HAS_ALIASES' => 1] if any alias is set
	*/
	public function testGetJSHintsTrue()
	{
		$plugin = $this->configurator->HTMLElements;
		$plugin->allowElement('A');
		$plugin->aliasElement('A', 'url');
		$this->assertSame(
			['HTMLELEMENTS_HAS_ALIASES' => 1],
			$plugin->getJSHints()
		);
	}
}