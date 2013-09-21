<?php

namespace s9e\TextFormatter\Tests\Bundles\S18;

use s9e\TextFormatter\Bundles\S18;
use s9e\TextFormatter\Bundles\S18\Helper;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Bundles\S18\Helper
*/
class HelperTest extends Test
{
	public function mockSMF()
	{
		if (!defined('SMF'))
		{
			include __DIR__ . '/env.php';
		}
	}

	/**
	* @testdox configureRenderer() has no effect if SMF is not loaded
	*/
	public function testConfigureRendererNone()
	{
		$renderer = $this->getMock(
			's9e\\TextFormatter\\Renderer',
			['renderRichText', 'setParameter', 'setParameters']
		);
		$renderer->expects($this->never())->method('setParameter');
		$renderer->expects($this->never())->method('setParameters');

		Helper::configureRenderer($renderer);
	}

	/**
	* @testdox configureRenderer() set lang strings and parameters if SMF is loaded
	* @runInSeparateProcess
	*/
	public function testConfigureRendererSMF()
	{
		$this->mockSMF();

		$params = [
			'IS_GECKO'      => 's:5:"gecko";',
			'IS_IE'         => 's:2:"ie";',
			'IS_OPERA'      => 's:5:"opera";',
			'L_CODE'        => 'C0d3',
			'L_CODE_SELECT' => 'Sel3ct',
			'L_QUOTE'       => 'Qu0te',
			'L_QUOTE_FROM'  => 'Qu0te fr0m',
			'L_SEARCH_ON'   => '0n',
			'SCRIPT_URL'    => '/path/to/smf',
			'SMILEYS_PATH'  => '/path/to/smileys/set/'
		];

		$renderer = $this->getMock(
			's9e\\TextFormatter\\Renderer',
			['renderRichText', 'setParameter', 'setParameters']
		);
		$renderer->expects($this->once())
		         ->method('setParameters')
		         ->with($params);

		Helper::configureRenderer($renderer);
	}

	/**
	* @testdox applyTimeformat() replaces numeric timestamps in [quote] with a human-readable date
	* @runInSeparateProcess
	*/
	public function testTimeformatQuote()
	{
		$this->mockSMF();

		$xml = S18::parse('[quote date=1344833733]Hello[/quote]');
		$this->assertSame(
			'<rt><QUOTE date="1344833733"><st>[quote date=1344833733]</st>Hello<et>[/quote]</et></QUOTE></rt>',
			$xml
		);
		$this->assertSame(
			'<rt><QUOTE date="s:10:&quot;1344833733&quot;;"><st>[quote date=1344833733]</st>Hello<et>[/quote]</et></QUOTE></rt>',
			Helper::applyTimeformat($xml)
		);
	}

	/**
	* @testdox s9e\TextFormatter\Bundles\S18\Helper::timeformat() replaces numeric timestamps in [time] with a human-readable date
	* @runInSeparateProcess
	*/
	public function testTimeformatTime()
	{
		$this->mockSMF();

		$xml = S18::parse('[time]1344833733[/time]');
		$this->assertSame(
			'<rt><TIME time="1344833733"><st>[time]</st>1344833733<et>[/time]</et></TIME></rt>',
			$xml
		);
		$this->assertSame(
			'<rt><TIME time="s:10:&quot;1344833733&quot;;"><st>[time]</st>1344833733<et>[/time]</et></TIME></rt>',
			Helper::applyTimeformat($xml)
		);
	}

	protected function _testIurl($url, $expected)
	{
		$this->assertSame(
			$expected,
			Helper::filterIurl(
				$url,
				[
					'allowedSchemes' => '//'
				],
				new Logger
			)
		);
	}

	/**
	* @testdox prependFtp() returns ftp:// URLs as-is
	*/
	public function testPrependFtpFtp()
	{
		$this->assertSame('ftp://example.org', Helper::prependFtp('ftp://example.org'));
	}

	/**
	* @testdox prependFtp() returns ftps:// URLs as-is
	*/
	public function testPrependFtpFtps()
	{
		$this->assertSame('ftps://example.org', Helper::prependFtp('ftps://example.org'));
	}

	/**
	* @testdox prependFtp() prepends ftp:// to non-ftp, non-ftps URLs
	*/
	public function testPrependFtpNonFtp()
	{
		$this->assertSame('ftp://example.org', Helper::prependFtp('example.org'));
	}

	/**
	* @testdox prependHttp() returns http:// URLs as-is
	*/
	public function testPrependHttpHttp()
	{
		$this->assertSame('http://example.org', Helper::prependHttp('http://example.org'));
	}

	/**
	* @testdox prependHttp() returns https:// URLs as-is
	*/
	public function testPrependHttpHttps()
	{
		$this->assertSame('https://example.org', Helper::prependHttp('https://example.org'));
	}

	/**
	* @testdox prependHttp() prepends http:// to non-http, non-https URLs
	*/
	public function testPrependHttpNonHttp()
	{
		$this->assertSame('http://example.org', Helper::prependHttp('example.org'));
	}

	/**
	* @testdox filterIurl() returns URL fragments as-is
	*/
	public function testFilterIurlFragment()
	{
		$this->_testIurl('#foo', '#foo');
	}

	/**
	* @testdox filterIurl() returns valid http:// URLs as-is
	*/
	public function testFilterIurlHttp()
	{
		$this->_testIurl('http://foo', 'http://foo');
	}

	/**
	* @testdox filterIurl() returns valid https:// URLs as-is
	*/
	public function testFilterIurlHttps()
	{
		$this->_testIurl('https://foo', 'https://foo');
	}

	/**
	* @testdox filterIurl() prepends http:// to non-http, non-https URLs
	*/
	public function testFilterIurlPrependHttp()
	{
		$this->_testIurl('www.example.org', 'http://www.example.org');
	}
}