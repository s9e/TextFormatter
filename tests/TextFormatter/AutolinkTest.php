<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* covers s9e\Toolkit\TextFormatter\Plugins\AutolinkConfig
* covers s9e\Toolkit\TextFormatter\Plugins\AutolinkParser
*/
class AutolinkTest extends Test
{
	public function testAutolinkPluginAutomaticallyCreatesAnUrlTag()
	{
		$this->cb->loadPlugin('Autolink');
		$this->cb->tagExists('URL');
	}

	public function testHttpUrlsAreLinkifiedByDefault()
	{
		$this->cb->loadPlugin('Autolink');

		$this->assertTransformation(
			'Go to http://www.example.com for more info',
			'<rt>Go to <URL url="http://www.example.com">http://www.example.com</URL> for more info</rt>',
			'Go to <a href="http://www.example.com">http://www.example.com</a> for more info'
		);
	}

	public function testHttpsUrlsAreLinkifiedByDefault()
	{
		$this->cb->loadPlugin('Autolink');

		$this->assertTransformation(
			'Go to https://www.example.com for more info',
			'<rt>Go to <URL url="https://www.example.com">https://www.example.com</URL> for more info</rt>',
			'Go to <a href="https://www.example.com">https://www.example.com</a> for more info'
		);
	}

	public function testFtpUrlsAreNotLinkifiedByDefault()
	{
		$this->cb->loadPlugin('Autolink');

		$this->assertTransformation(
			'Go to ftp://www.example.com for more info',
			'<pt>Go to ftp://www.example.com for more info</pt>',
			'Go to ftp://www.example.com for more info'
		);
	}

	public function testFtpUrlsAreLinkifiedIfTheSchemeHasBeenAllowedInConfigBuilder()
	{
		$this->cb->loadPlugin('Autolink');

		$this->cb->allowScheme('ftp');

		$this->assertTransformation(
			'Go to ftp://www.example.com for more info',
			'<rt>Go to <URL url="ftp://www.example.com">ftp://www.example.com</URL> for more info</rt>',
			'Go to <a href="ftp://www.example.com">ftp://www.example.com</a> for more info'
		);
	}
}