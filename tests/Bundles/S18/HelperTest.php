<?php

namespace s9e\TextFormatter\Tests\Bundles\S18;

use s9e\TextFormatter\Bundles\S18;
use s9e\TextFormatter\Bundles\S18\Helper;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Bundles\S18\Helper
* @group runs-in-separate-process
*/
class HelperTest extends Test
{
	public function mockSMF()
	{
		include __DIR__ . '/env.php';
	}

	/**
	* @testdox configureParser() has no effect if SMF is not loaded
	*/
	public function testConfigureParserNone()
	{
		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->never())->method('disablePlugin');
		$parser->expects($this->never())->method('disableTag');

		Helper::configureParser($parser);
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
	* @testdox applyTimeformat() replaces numeric timestamps in [quote] with a human-readable date
	*/
	public function testTimeformatQuote()
	{
		if (function_exists('timeformat'))
		{
			$this->markTestSkipped('timeformat() should not exist');
		}

		$xml = S18::parse('[quote date=1344833733]Hello[/quote]');
		$this->assertSame(
			'<r><QUOTE date="1344833733"><s>[quote date=1344833733]</s>Hello<e>[/quote]</e></QUOTE></r>',
			$xml
		);
		$this->assertSame(
			'<r><QUOTE date="August 13, 2012, 04:55:33 AM"><s>[quote date=1344833733]</s>Hello<e>[/quote]</e></QUOTE></r>',
			Helper::applyTimeformat($xml)
		);
	}

	/**
	* @testdox s9e\TextFormatter\Bundles\S18\Helper::timeformat() replaces numeric timestamps in [time] with a human-readable date
	*/
	public function testTimeformatTime()
	{
		if (function_exists('timeformat'))
		{
			$this->markTestSkipped('timeformat() should not exist');
		}

		$xml = S18::parse('[time]1344833733[/time]');
		$this->assertSame(
			'<r><TIME time="1344833733"><s>[time]</s>1344833733<e>[/time]</e></TIME></r>',
			$xml
		);
		$this->assertSame(
			'<r><TIME time="August 13, 2012, 04:55:33 AM"><s>[time]</s>1344833733<e>[/time]</e></TIME></r>',
			Helper::applyTimeformat($xml)
		);
	}

	/**
	* @testdox applyTimeformat() uses timeformat() if it exists
	* @runInSeparateProcess
	*/
	public function testTimeformatQuoteSMF()
	{
		$this->mockSMF();

		$xml = S18::parse('[quote date=1344833733]Hello[/quote]');
		$this->assertSame(
			'<r><QUOTE date="1344833733"><s>[quote date=1344833733]</s>Hello<e>[/quote]</e></QUOTE></r>',
			$xml
		);
		$this->assertSame(
			'<r><QUOTE date="s:10:&quot;1344833733&quot;;"><s>[quote date=1344833733]</s>Hello<e>[/quote]</e></QUOTE></r>',
			Helper::applyTimeformat($xml)
		);
	}

	/**
	* @testdox configureParser() disables the Autoemail and Autolink plugins if $modSettings['autoLinkUrls'] is falsy
	* @runInSeparateProcess
	*/
	public function testConfigureParserDisableAutoPlugins()
	{
		global $modSettings;

		$this->mockSMF();
		$modSettings['autoLinkUrls'] = 0;

		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->at(0))
		       ->method('disablePlugin')
		       ->with('Autoemail');

		$parser->expects($this->at(1))
		       ->method('disablePlugin')
		       ->with('Autolink');

		Helper::configureParser($parser);
	}

	/**
	* @testdox configureParser() disables the BBCodes plugin if $modSettings['enableBBC'] is falsy
	* @runInSeparateProcess
	*/
	public function testConfigureParserDisableBBCodes()
	{
		global $modSettings;

		$this->mockSMF();
		$modSettings['enableBBC'] = 0;

		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->once())
		       ->method('disablePlugin')
		       ->with('BBCodes');

		Helper::configureParser($parser);
	}

	/**
	* @testdox configureParser() disables the HTMLElements plugin if $modSettings['enablePostHTML'] is falsy
	* @runInSeparateProcess
	*/
	public function testConfigureParserDisableHTMLElements()
	{
		global $modSettings;

		$this->mockSMF();
		$modSettings['enablePostHTML'] = 0;

		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->once())
		       ->method('disablePlugin')
		       ->with('HTMLElements');

		Helper::configureParser($parser);
	}

	/**
	* @testdox configureParser() disables the BBCodes found in $modSettings['disabledBBC']
	* @runInSeparateProcess
	*/
	public function testConfigureParserDisableTags()
	{
		global $modSettings;

		$this->mockSMF();
		$modSettings['disabledBBC'] = 'bdo,green';

		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->at(0))
		       ->method('disableTag')
		       ->with('BDO');

		$parser->expects($this->at(1))
		       ->method('disableTag')
		       ->with('GREEN');

		Helper::configureParser($parser);
	}

	/**
	* @testdox configureParser() disables the FLASH tag if $modSettings['enableEmbeddedFlash'] is falsy
	* @runInSeparateProcess
	*/
	public function testConfigureParserDisableFlash()
	{
		global $modSettings;

		$this->mockSMF();
		$modSettings['enableEmbeddedFlash'] = 0;

		$parser = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		               ->disableOriginalConstructor()
		               ->getMock();

		$parser->expects($this->once())
		       ->method('disableTag')
		       ->with('FLASH');

		Helper::configureParser($parser);
	}

	/**
	* @testdox configureRenderer() set lang strings and parameters if SMF is loaded
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