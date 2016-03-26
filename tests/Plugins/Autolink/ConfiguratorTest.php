<?php

namespace s9e\TextFormatter\Tests\Plugins\Autolink;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "URL" tag with an "url" attribute with a "#url" filter
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Autolink');
		$this->assertTrue($this->configurator->tags->exists('URL'));

		$tag = $this->configurator->tags->get('URL');
		$this->assertTrue($tag->attributes->exists('url'));

		$attribute = $tag->attributes->get('url');
		$this->assertTrue($attribute->filterChain->contains(new UrlFilter));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('URL');
		$this->configurator->plugins->load('Autolink');

		$this->assertSame($tag, $this->configurator->tags->get('URL'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autolink', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autolink', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['URL']->attributes->exists('bar'));
	}

	/**
	* @testdox The matchWww can be read
	*/
	public function testMatchWwwRead()
	{
		$this->assertFalse($this->configurator->Autolink->matchWww);
	}

	/**
	* @testdox The matchWww can be written
	*/
	public function testMatchWwwWrite()
	{
		$this->assertFalse($this->configurator->Autolink->matchWww);
		$this->configurator->Autolink->matchWww = true;
		$this->assertTrue($this->configurator->Autolink->matchWww);
	}

	/**
	* @testdox Has a quickMatch if matchWww is disabled
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autolink')->asConfig()
		);
	}

	/**
	* @testdox Does not have a quickMatch if matchWww is enabled
	*/
	public function testConfigNoQuickMatch()
	{
		$this->assertArrayNotHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autolink', ['matchWww' => true])->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autolink')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('Autolink')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('URL', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttributeName()
	{
		$config = $this->configurator->plugins->load('Autolink')->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('url', $config['attrName']);
	}

	/**
	* @testdox The regexp matches the URL in 'foo http://www.bar.com baz'
	*/
	public function testRegexp()
	{
		$text = 'foo http://www.bar.com baz';
		$url  = 'http://www.bar.com';

		$config = $this->configurator->plugins->load('Autolink')->asConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}

	/**
	* @testdox The regexp matches the URL in 'FOO HTTP://WWW.BAR.COM BAZ'
	*/
	public function testRegexpCaseInsensitive()
	{
		$text = 'FOO HTTP://WWW.BAR.COM BAZ';
		$url  = 'HTTP://WWW.BAR.COM';

		$config = $this->configurator->plugins->load('Autolink')->asConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}

	/**
	* @testdox The regexp matches the URL in 'foo http://www.bar.com/index.php?arr[foo]=1 baz'
	*/
	public function testRegexpBrackets()
	{
		$text = 'foo http://www.bar.com/index.php?arr[foo]=1 baz';
		$url  = 'http://www.bar.com/index.php?arr[foo]=1';

		$config = $this->configurator->plugins->load('Autolink')->asConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}

	/**
	* @testdox The regexp matches the URL in 'foo [http://www.bar.com/index.php?foo=1] baz'
	*/
	public function testRegexpInBrackets()
	{
		$text = 'foo [http://www.bar.com/index.php?foo=1] baz';
		$url  = 'http://www.bar.com/index.php?foo=1';

		$config = $this->configurator->plugins->load('Autolink')->asConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}

	/**
	* @testdox The regexp matches the URL in 'foo [http://www.bar.com/index.php?arr[foo]=1] baz'
	*/
	public function testRegexpBracketsInBrackets()
	{
		$text = 'foo [http://www.bar.com/index.php?arr[foo]=1] baz';
		$url  = 'http://www.bar.com/index.php?arr[foo]=1';

		$config = $this->configurator->plugins->load('Autolink')->asConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Autolink');

		$this->assertSame(
			$this->configurator->tags['URL'],
			$plugin->getTag()
		);
	}
}