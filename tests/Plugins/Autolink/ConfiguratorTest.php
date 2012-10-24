<?php

namespace s9e\TextFormatter\Tests\Plugins\Autolink;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Autolink\Configurator;

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
		$this->assertTrue($attribute->filterChain->contains('#url'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autolink', array('tagName' => 'FOO'));
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autolink', array('attrName' => 'bar'));
		$this->assertTrue($this->configurator->tags['URL']->attributes->exists('bar'));
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testToConfig()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autolink')->toConfig()
		);
	}

	/**
	* @testdox The regexp matches the URL in 'foo http://www.bar.com baz'
	*/
	public function testRegexp()
	{
		$text = 'foo http://www.bar.com baz';
		$url  = 'http://www.bar.com';

		$config = $this->configurator->plugins->load('Autolink')->toConfig();
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

		$config = $this->configurator->plugins->load('Autolink')->toConfig();
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

		$config = $this->configurator->plugins->load('Autolink')->toConfig();
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

		$config = $this->configurator->plugins->load('Autolink')->toConfig();
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

		$config = $this->configurator->plugins->load('Autolink')->toConfig();
		$this->assertRegExp($config['regexp'], $text);

		preg_match($config['regexp'], $text, $m);
		$this->assertSame($url, $m[0]);
	}
}