<?php

namespace s9e\TextFormatter\Tests\Bundles;

use s9e\TextFormatter\Bundles\S18;
use s9e\TextFormatter\Bundles\S18\Helper;

/**
* @covers s9e\TextFormatter\Bundles\S18
* @covers s9e\TextFormatter\Bundles\S18\Helper
*/
class S18Test extends AbstractTest
{
	/**
	* @testdox s9e\TextFormatter\Bundles\S18\Helper::timeformat() replaces numeric timestamps in [quote] with a human-readable date
	*/
	public function testTimeformatQuote()
	{
		$xml = S18::parse('[quote date=1344833733]Hello[/quote]');
		$this->assertSame(
			'<rt><QUOTE date="1344833733"><st>[quote date=1344833733]</st>Hello<et>[/quote]</et></QUOTE></rt>',
			$xml
		);
		$this->assertSame(
			'<rt><QUOTE date="&lt;human-readable date&gt;"><st>[quote date=1344833733]</st>Hello<et>[/quote]</et></QUOTE></rt>',
			Helper::timeformat(
				$xml,
				function ()
				{
					return '<human-readable date>';
				}
			)
		);
	}

	/**
	* @testdox s9e\TextFormatter\Bundles\S18\Helper::timeformat() replaces numeric timestamps in [time] with a human-readable date
	*/
	public function testTimeformatTime()
	{
		$xml = S18::parse('[time]1344833733[/time]');
		$this->assertSame(
			'<rt><TIME time="1344833733"><st>[time]</st>1344833733<et>[/time]</et></TIME></rt>',
			$xml
		);
		$this->assertSame(
			'<rt><TIME time="&lt;human-readable date&gt;"><st>[time]</st>1344833733<et>[/time]</et></TIME></rt>',
			Helper::timeformat(
				$xml,
				function ()
				{
					return '<human-readable date>';
				}
			)
		);
	}
}