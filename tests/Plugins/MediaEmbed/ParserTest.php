<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\MediaEmbed\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	protected static function populateCache($entries)
	{
		$cacheDir = __DIR__ . '/../../.cache';

		if (!file_exists($cacheDir))
		{
			$cacheDir = sys_get_temp_dir();
		}

		$prefix = $suffix = '';
		if (extension_loaded('zlib'))
		{
			$prefix  = 'compress.zlib://';
			$suffix  = '.gz';
		}

		foreach ($entries as $url => $content)
		{
			file_put_contents(
				$prefix . $cacheDir . '/http.' . crc32($url) . $suffix,
				$content
			);
		}

		return $cacheDir;
	}

	/**
	* Run a test that involves scraping without causing a fail if the remote server errors out
	*/
	protected function runScrapingTest($methodName, $args)
	{
		try
		{
			call_user_func_array([$this, $methodName], $args);
		}
		catch (\PHPUnit_Framework_Error_Warning $e)
		{
			$msg = $e->getMessage();

			if (strpos($msg, 'HTTP request failed')  !== false
			 || strpos($msg, 'Connection timed out') !== false
			 || strpos($msg, 'Connection refused')   !== false)
			{
				$this->markTestSkipped($msg);
			}

			throw $e;
		}
	}

	/**
	* @testdox scrape() does not do anything if the tag does not have a "url" attribute
	*/
	public function testScrapeNoUrl()
	{
		$tag = new Tag(Tag::START_TAG, 'MEDIA', 0, 0);

		$this->assertTrue(Parser::scrape($tag, []));
	}

	/**
	* @testdox The [MEDIA] tag transfers its priority to the tag it creates
	*/
	public function testTagPriority()
	{
		$newTag = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Tag')
		               ->disableOriginalConstructor()
		               ->setMethods(['setAttributes', 'setSortPriority'])
		               ->getMock();

		$newTag->expects($this->once())
		       ->method('setSortPriority')
		       ->with(123);

		$tagStack = $this->getMockBuilder('s9e\\TextFormatter\\Parser')
		                 ->disableOriginalConstructor()
		                 ->setMethods(['addSelfClosingTag'])
		                 ->getMock();

		$tagStack->expects($this->once())
		         ->method('addSelfClosingTag')
		         ->will($this->returnValue($newTag));

		$tag = new Tag(Tag::START_TAG, 'MEDIA', 0, 0);
		$tag->setAttribute('media', 'foo');
		$tag->setSortPriority(123);

		Parser::filterTag($tag, $tagStack, []);
	}

	/**
	* @testdox Abstract tests (not tied to bundled sites)
	* @dataProvider getAbstractTests
	*/
	public function testAbstract()
	{
		call_user_func_array([$this, 'testParsing'], func_get_args());
	}

	public function getAbstractTests()
	{
		return [
			[
				// Multiple "match" in scrape
				'http://example.invalid/123',
				'<r><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'    => 'example.invalid',
							'scrape'  => [
								'match'   => ['/XXX/', '/123/'],
								'extract' => "!^(?'id'[0-9]+)$!"
							],
							'template' => ''
						]
					);
				}
			],
			[
				// Multiple "extract" in scrape
				'http://example.invalid/123',
				'<r><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'    => 'example.invalid',
							'scrape'  => [
								'match'   => '/./',
								'extract' => ['/foo/', "!^(?'id'[0-9]+)$!"]
							],
							'template' => ''
						]
					);
				}
			],
			[
				// Multiple scrapes
				'http://example.invalid/123',
				'<r><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'    => 'example.invalid',
							'scrape'  => [
								[
									'match'   => '/./',
									'extract' => '/foo/'
								],
								[
									'match'   => '/./',
									'extract' => "!^(?'id'[0-9]+)$!"
								]
							],
							'template' => ''
						]
					);
				}
			],
		];
	}

	/**
	* @testdox Scraping tests
	* @dataProvider getScrapingTests
	* @group needs-network
	*/
	public function testScraping()
	{
		$this->runScrapingTest('testParsing', func_get_args());
	}

	public function getScrapingTests()
	{
		return [
			[
				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
				'<r><BANDCAMP album_id="1122163921" url="http://proleter.bandcamp.com/album/curses-from-past-times-ep">http://proleter.bandcamp.com/album/curses-from-past-times-ep</BANDCAMP></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://proleter.bandcamp.com/track/muhammad-ali',
				'<r><BANDCAMP album_id="1122163921" track_id="3496015802" track_num="7" url="http://proleter.bandcamp.com/track/muhammad-ali">http://proleter.bandcamp.com/track/muhammad-ali</BANDCAMP></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://therunons.bandcamp.com/track/still-feel',
				'<r><BANDCAMP track_id="2146686782" url="http://therunons.bandcamp.com/track/still-feel">http://therunons.bandcamp.com/track/still-feel</BANDCAMP></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://www.bbc.com/news/science-environment-29232523',
				'<r><BBCNEWS ad_site="/news/science_and_environment/" playlist="/news/science-environment-29232523A" poster="/media/images/77632000/jpg/_77632870_77632869.jpg" url="http://www.bbc.com/news/science-environment-29232523">http://www.bbc.com/news/science-environment-29232523</BBCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bbcnews');
				}
			],
			[
				'http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725',
				'<r><BLIP id="AYOW3REC" url="http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725">http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725</BLIP></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/blip-on-blip/damian-bruno-and-vinyl-rewind-blip-on-blip-58-5226104',
				'<r><BLIP id="zEiCvv1cAg" url="http://blip.tv/blip-on-blip/damian-bruno-and-vinyl-rewind-blip-on-blip-58-5226104">http://blip.tv/blip-on-blip/damian-bruno-and-vinyl-rewind-blip-on-blip-58-5226104</BLIP></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://thecolbertreport.cc.com/videos/gh6urb/neil-degrasse-tyson-pt--1',
				'<r><COLBERTNATION id="mgid:arc:video:colbertnation.com:676d3a42-4c19-47e0-9509-f333fa76b4eb" url="http://thecolbertreport.cc.com/videos/gh6urb/neil-degrasse-tyson-pt--1">http://thecolbertreport.cc.com/videos/gh6urb/neil-degrasse-tyson-pt--1</COLBERTNATION></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('colbertnation');
				}
			],
			[
				'http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
				'<r><COMEDYCENTRAL id="mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea" url="http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats">http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats</COMEDYCENTRAL></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('comedycentral');
				}
			],
			[
				'http://tosh.cc.com/video-clips/aet4lh/rc-car-crash',
				'<r><COMEDYCENTRAL id="mgid:arc:video:tosh.comedycentral.com:3b516128-7054-4439-a01e-0aa9c0b020ac" url="http://tosh.cc.com/video-clips/aet4lh/rc-car-crash">http://tosh.cc.com/video-clips/aet4lh/rc-car-crash</COMEDYCENTRAL></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('comedycentral');
				}
			],
			[
				'http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508',
				'<r><DAILYSHOW id="mgid:arc:video:thedailyshow.com:9fd84f1c-a137-4998-b891-14a57b4ac0f5" url="http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508">http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508</DAILYSHOW></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-',
				'<r><DAILYSHOW id="mgid:arc:video:thedailyshow.com:627cc3c2-4218-4a78-bf1d-c8258f4db2f8" url="http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-">http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-</DAILYSHOW></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://thedailyshow.cc.com/videos/elvsf4/what-not-to-buy',
				'<r><DAILYSHOW id="mgid:arc:video:thedailyshow.com:e2ed81f7-a322-4ef9-82d9-12ba07e5c319" url="http://thedailyshow.cc.com/videos/elvsf4/what-not-to-buy">http://thedailyshow.cc.com/videos/elvsf4/what-not-to-buy</DAILYSHOW></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://thedailyshow.cc.com/extended-interviews/rpgevm/exclusive-matt-taibbi-extended-interview',
				'<r><DAILYSHOW id="mgid:arc:playlist:thedailyshow.com:85ebd39c-9fea-44f3-9da2-f3088cab195d" url="http://thedailyshow.cc.com/extended-interviews/rpgevm/exclusive-matt-taibbi-extended-interview">http://thedailyshow.cc.com/extended-interviews/rpgevm/exclusive-matt-taibbi-extended-interview</DAILYSHOW></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://www.ebay.co.uk/itm/Converse-Classic-Chuck-Taylor-Low-Trainer-Sneaker-All-Star-OX-NEW-sizes-Shoes-/230993099153',
				'<r><EBAY id="230993099153" lang="en_GB" url="http://www.ebay.co.uk/itm/Converse-Classic-Chuck-Taylor-Low-Trainer-Sneaker-All-Star-OX-NEW-sizes-Shoes-/230993099153">http://www.ebay.co.uk/itm/Converse-Classic-Chuck-Taylor-Low-Trainer-Sneaker-All-Star-OX-NEW-sizes-Shoes-/230993099153</EBAY></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://8tracks.com/fingerlickengood/just-nujabes',
				'<r><EIGHTTRACKS id="2485118">http://8tracks.com/fingerlickengood/just-nujabes</EIGHTTRACKS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('eighttracks');
				}
			],
			[
				'http://www.gametrailers.com/videos/jz8rt1/tom-clancy-s-the-division-vgx-2013--world-premiere-featurette-',
				'<r><GAMETRAILERS id="mgid:arc:video:gametrailers.com:85dee3c3-60f6-4b80-8124-cf3ebd9d2a6c" url="http://www.gametrailers.com/videos/jz8rt1/tom-clancy-s-the-division-vgx-2013--world-premiere-featurette-">http://www.gametrailers.com/videos/jz8rt1/tom-clancy-s-the-division-vgx-2013--world-premiere-featurette-</GAMETRAILERS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gametrailers');
				}
			],
			[
				'http://www.gametrailers.com/reviews/zalxz0/crimson-dragon-review',
				'<r><GAMETRAILERS id="mgid:arc:video:gametrailers.com:31c93ab8-fe77-4db2-bfee-ff37837e6704" url="http://www.gametrailers.com/reviews/zalxz0/crimson-dragon-review">http://www.gametrailers.com/reviews/zalxz0/crimson-dragon-review</GAMETRAILERS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gametrailers');
				}
			],
			[
				'http://www.gametrailers.com/full-episodes/zdzfok/pop-fiction-episode-40--jak-ii--sandover-village',
				'<r><GAMETRAILERS id="mgid:arc:episode:gametrailers.com:1e287a4e-b795-4c7f-9d48-1926eafb5740" url="http://www.gametrailers.com/full-episodes/zdzfok/pop-fiction-episode-40--jak-ii--sandover-village">http://www.gametrailers.com/full-episodes/zdzfok/pop-fiction-episode-40--jak-ii--sandover-village</GAMETRAILERS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gametrailers');
				}
			],
			[
				'http://gty.im/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" url="http://gty.im/3232182" width="594">http://gty.im/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertRegexp'
			],
			[
				'http://www.gettyimages.com/detail/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" url="http://www.gettyimages.com/detail/3232182" width="594">http://www.gettyimages.com/detail/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertRegexp'
			],
			[
				'http://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182',
				'(<r><GETTY et="[-\\w]{22}" height="399" id="3232182" sig="[-\\w]{43}=" url="http://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182" width="594">http://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182</GETTY></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertRegexp'
			],
			[
				"http://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667",
				'(<r><GETTY et="[-\\w]{22}" height="594" id="494028667" sig="[-\\w]{43}=" url="http://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667" width="396">http://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667</GETTY></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertRegexp'
			],
			[
				"http://www.gettyimages.co.jp/detail/ニュース写真/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-ニュース写真/494028667",
				'(<r><GETTY et="[-\\w]{22}" height="594" id="494028667" sig="[-\\w]{43}=" url="http://www.gettyimages.co.jp/detail/%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-%E3%83%8B%E3%83%A5%E3%83%BC%E3%82%B9%E5%86%99%E7%9C%9F/494028667" width="396">http://www.gettyimages.co.jp/detail/ニュース写真/cher-lloyd-promotes-the-new-cd-sorry-im-late-at-nbc-experience-ニュース写真/494028667</GETTY></r>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				null,
				'assertRegexp'
			],
			[
				'http://gfycat.com/SereneIllfatedCapybara',
				'<r><GFYCAT height="338" id="SereneIllfatedCapybara" url="http://gfycat.com/SereneIllfatedCapybara" width="600">http://gfycat.com/SereneIllfatedCapybara</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'http://giant.gfycat.com/SereneIllfatedCapybara.gif',
				'<r><GFYCAT height="338" id="SereneIllfatedCapybara" url="http://giant.gfycat.com/SereneIllfatedCapybara.gif" width="600">http://giant.gfycat.com/SereneIllfatedCapybara.gif</GFYCAT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'https://plus.google.com/+FeliciaDay/posts/XMABm8rLvRW',
				'<r><GOOGLEPLUS oid="110286587261352351537" pid="XMABm8rLvRW" url="https://plus.google.com/+FeliciaDay/posts/XMABm8rLvRW">https://plus.google.com/+FeliciaDay/posts/XMABm8rLvRW</GOOGLEPLUS></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'http://grooveshark.com/s/Soul+Below/4zGL7i?src=5',
				'<r><GROOVESHARK songid="35292216" url="http://grooveshark.com/s/Soul+Below/4zGL7i?src=5">http://grooveshark.com/s/Soul+Below/4zGL7i?src=5</GROOVESHARK></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5',
				'<r><GROOVESHARK songid="35292216" url="http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5">http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5</GROOVESHARK></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://www.hulu.com/watch/484180',
				'<r><HULU id="zPFCgxncn97IFkqEnZ-kRA" url="http://www.hulu.com/watch/484180">http://www.hulu.com/watch/484180</HULU></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('hulu');
				}
			],
			[
				'http://www.indiegogo.com/projects/gameheart-redesigned',
				'<r><INDIEGOGO id="513633" url="http://www.indiegogo.com/projects/gameheart-redesigned">http://www.indiegogo.com/projects/gameheart-redesigned</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.indiegogo.com/projects/5050-years-a-documentary',
				'<r><INDIEGOGO id="535215" url="http://www.indiegogo.com/projects/5050-years-a-documentary">http://www.indiegogo.com/projects/5050-years-a-documentary</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'https://archive.org/details/BillGate99',
				'<r><INTERNETARCHIVE height="240" id="BillGate99" url="https://archive.org/details/BillGate99" width="320">https://archive.org/details/BillGate99</INTERNETARCHIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'https://archive.org/details/DFTS2014-05-30',
				'<r><INTERNETARCHIVE height="50" id="DFTS2014-05-30&amp;playlist=1" url="https://archive.org/details/DFTS2014-05-30" width="300">https://archive.org/details/DFTS2014-05-30</INTERNETARCHIVE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('internetarchive');
				}
			],
			[
				'http://i.mixcloud.com/CH9VU9',
				'<r><MIXCLOUD id="Butjes/third-mix" url="http://i.mixcloud.com/CH9VU9">http://i.mixcloud.com/CH9VU9</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725',
				'<r><MSNBC id="n_farrow_moon_140709_257794" url="http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725">http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725</MSNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('msnbc');
				}
			],
			[
				'http://dialhforheroclix.podbean.com/e/dial-h-for-heroclix-episode-46-all-ya-need-is-love/',
				'<r><PODBEAN id="5169420" url="http://dialhforheroclix.podbean.com/e/dial-h-for-heroclix-episode-46-all-ya-need-is-love/">http://dialhforheroclix.podbean.com/e/dial-h-for-heroclix-episode-46-all-ya-need-is-love/</PODBEAN></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('podbean');
				}
			],
			[
				'https://www.rdio.com/artist/Hannibal_Buress/album/Animal_Furnace/track/Hands-Free/',
				'<r><RDIO id="QitDVOn7" url="https://www.rdio.com/artist/Hannibal_Buress/album/Animal_Furnace/track/Hands-Free/">https://www.rdio.com/artist/Hannibal_Buress/album/Animal_Furnace/track/Hands-Free/</RDIO></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('rdio');
				}
			],
			[
				'http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/',
				'<r><RUTUBE id="6613980" url="http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/">http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/</RUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites',
				'<r><SLIDESHARE id="21112125" url="http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites">http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites</SLIDESHARE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				'https://soundcloud.com/matt0753/iroh-ii-deep-voice/s-UpqTm',
				'<r><SOUNDCLOUD id="https://soundcloud.com/matt0753/iroh-ii-deep-voice/s-UpqTm" secret_token="s-UpqTm" track_id="51465673" url="https://soundcloud.com/matt0753/iroh-ii-deep-voice/s-UpqTm">https://soundcloud.com/matt0753/iroh-ii-deep-voice/s-UpqTm</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/swami-john/sets/auto-midnight-scrap-heap/s-0WDep',
				'<r><SOUNDCLOUD id="https://soundcloud.com/swami-john/sets/auto-midnight-scrap-heap/s-0WDep" playlist_id="3111458" secret_token="s-0WDep" url="https://soundcloud.com/swami-john/sets/auto-midnight-scrap-heap/s-0WDep">https://soundcloud.com/swami-john/sets/auto-midnight-scrap-heap/s-0WDep</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.sportsnet.ca/soccer/west-ham-2-hull-2/',
				'<r><SPORTSNET id="3786409870001" url="http://www.sportsnet.ca/soccer/west-ham-2-hull-2/">http://www.sportsnet.ca/soccer/west-ham-2-hull-2/</SPORTSNET></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('sportsnet');
				}
			],
			[
				'http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode',
				'<r><TEAMCOCO id="73784" url="http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode">http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode</TEAMCOCO></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('teamcoco');
				}
			],
			[
				'http://www.traileraddict.com/robocop-2013/tv-spot-meet-the-future-ii',
				'<r><TRAILERADDICT id="85253" url="http://www.traileraddict.com/robocop-2013/tv-spot-meet-the-future-ii">http://www.traileraddict.com/robocop-2013/tv-spot-meet-the-future-ii</TRAILERADDICT></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('traileraddict');
				}
			],
			[
				'http://www.twitch.tv/m/57217',
				'<r><TWITCH archive_id="435873548" channel="wcs_america" url="http://www.twitch.tv/m/57217">http://www.twitch.tv/m/57217</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.ustream.tv/channel/ps4-ustream-gameplay',
				'<r><USTREAM cid="16234409" url="http://www.ustream.tv/channel/ps4-ustream-gameplay">http://www.ustream.tv/channel/ps4-ustream-gameplay</USTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://vkontakte.ru/video-7016284_163645555',
				'<r><VK hash="eb5d7a5e6e1d8b71" oid="-7016284" url="http://vkontakte.ru/video-7016284_163645555" vid="163645555">http://vkontakte.ru/video-7016284_163645555</VK></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'http://vk.com/video226156999_168963041',
				'<r><VK hash="9050a9cce6465c9e" oid="226156999" url="http://vk.com/video226156999_168963041" vid="168963041">http://vk.com/video226156999_168963041</VK></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'http://vk.com/newmusicvideos?z=video-13895667_161988074',
				'<r><VK hash="de860a8e4fbe45c9" oid="-13895667" url="http://vk.com/newmusicvideos?z=video-13895667_161988074" vid="161988074">http://vk.com/newmusicvideos?z=video-13895667_161988074</VK></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vk');
				}
			],
			[
				'http://vube.com/s/v7JeV6',
				'<r><VUBE id="wovO34HWbY" url="http://vube.com/s/v7JeV6">http://vube.com/s/v7JeV6</VUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vube');
				}
			],
			[
				'http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0',
				'<r><WSHH id="63133" url="http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0">http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61',
				'<r><WSHH id="63175" url="http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61">http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://m.worldstarhiphop.com/apple/video.php?v=wshh9yky3fx1Sj96E2mo',
				'<r><WSHH id="71468" url="http://m.worldstarhiphop.com/apple/video.php?v=wshh9yky3fx1Sj96E2mo">http://m.worldstarhiphop.com/apple/video.php?v=wshh9yky3fx1Sj96E2mo</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('wshh');
				}
			],
		];
	}

	/**
	* @testdox Scraping+rendering tests
	* @dataProvider getScrapingRenderingTests
	* @group needs-network
	*/
	public function testScrapingRendering()
	{
		$this->runScrapingTest('testRendering', func_get_args());
	}

	public function getScrapingRenderingTests()
	{
		return [
			[
				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
				'<iframe width="400" height="400" allowfullscreen="" frameborder="0" scrolling="no" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/album=1122163921"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://proleter.bandcamp.com/track/muhammad-ali',
				'<iframe width="400" height="400" allowfullscreen="" frameborder="0" scrolling="no" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/album=1122163921/t=7"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://therunons.bandcamp.com/track/still-feel',
				'<iframe width="400" height="400" allowfullscreen="" frameborder="0" scrolling="no" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/track=2146686782"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://www.cbsnews.com/videos/is-the-us-stock-market-rigged',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="425" height="279" data="http://www.cbsnews.com/common/video/cbsnews_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="pType=embed&amp;si=254&amp;pid=W4MVSOaNEYMq"><embed type="application/x-shockwave-flash" width="425" height="279" allowfullscreen="" src="http://www.cbsnews.com/common/video/cbsnews_player.swf" flashvars="pType=embed&amp;si=254&amp;pid=W4MVSOaNEYMq"></object>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
				'<iframe width="512" height="288" src="//media.mtvnservices.com/embed/mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('comedycentral');
				}
			],
			[
				'http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508',
				'<iframe width="512" height="288" src="//media.mtvnservices.com/embed/mgid:arc:video:thedailyshow.com:9fd84f1c-a137-4998-b891-14a57b4ac0f5" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://www.ebay.co.uk/itm/Converse-Classic-Chuck-Taylor-Low-Trainer-Sneaker-All-Star-OX-NEW-sizes-Shoes-/230993099153',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="355" height="300" data="http://togo.ebay.com/togo/togo.swf?2008013100"><param name="allowfullscreen" value="true"><param name="flashvars" value="base=http://togo.ebay.com/togo/&amp;mode=normal&amp;itemid=230993099153&amp;lang=en-GB"><embed type="application/x-shockwave-flash" src="http://togo.ebay.com/togo/togo.swf?2008013100" width="355" height="300" allowfullscreen="" flashvars="base=http://togo.ebay.com/togo/&amp;mode=normal&amp;itemid=230993099153&amp;lang=en-GB"></object>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://www.gettyimages.com/detail/3232182',
				'(<iframe width="594" height="448" src="//embed.gettyimages.com/embed/3232182\?et=[-\\w]{22}&amp;sig=[-\\w]{43}=" allowfullscreen="" frameborder="0" scrolling="no"></iframe>)',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('getty');
				},
				'assertRegexp'
			],
			[
				'http://gfycat.com/SereneIllfatedCapybara',
				'<iframe width="600" height="338" src="//gfycat.com/iframe/SereneIllfatedCapybara" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('gfycat');
				}
			],
			[
				'http://grooveshark.com/s/Soul+Below/4zGL7i?src=5',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="400" height="40" data="//grooveshark.com/songWidget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID=&amp;songID=35292216"><embed type="application/x-shockwave-flash" src="//grooveshark.com/songWidget.swf" width="400" height="40" allowfullscreen="" flashvars="playlistID=&amp;songID=35292216"></object>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'https://soundcloud.com/matt0753/iroh-ii-deep-voice/s-UpqTm',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/51465673&amp;secret_token=s-UpqTm"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.ustream.tv/channel/ps4-ustream-gameplay',
				'<iframe width="480" height="302" allowfullscreen="" frameborder="0" scrolling="no" src="//www.ustream.tv/embed/16234409"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('ustream');
				}
			],
		];
	}

	public function getParsingTests()
	{
		return [
			// =================================================================
			// Abstract tests
			// =================================================================
			[
				// Ensure that non-HTTP URLs don't get scraped
				'[media]invalid://example.org/123[/media]',
				'<t>[media]invalid://example.org/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.org',
							'scrape' => [
								'match'   => '/./',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe' => ['width' => 1, 'height' => 1, 'src' => '{@id}']
						]
					);
				}
			],
			[
				// Ensure that invalid URLs don't get scraped
				'[media]http://example.invalid/123?x"> foo="bar[/media]',
				'<t>[media]http://example.invalid/123?x"&gt; foo="bar[/media]</t>',
				['captureURLs' => false],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = self::populateCache([
						'http://example.invalid/123?x"> foo="bar' => '456'
					]);

					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								'match'   => '/./',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe' => ['width' => 1, 'height' => 1, 'src' => '{@id}']
						]
					);
				}
			],
			[
				// Ensure that we don't scrape the URL if it doesn't match
				'[media]http://example.invalid/123[/media]',
				'<t>[media]http://example.invalid/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.invalid',
							'scrape' => [
								'match'   => '/XXX/',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe' => ['width' => 1, 'height' => 1, 'src' => '{@id}']
						]
					);
				}
			],
			[
				// Ensure that we don't scrape if the attributes are already filled
				'http://example.invalid/123',
				'<r><EXAMPLE id="12" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'    => 'example.invalid',
							'extract' => "#/(?'id'[0-9]{2})#",
							'scrape'  => [
								'match'   => '/./',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe'  => ['width' => 1, 'height' => 1, 'src' => '{@id}']
						]
					);
				}
			],
			[
				'[media]http://foo.example.org/123[/media]',
				'<r><X2 id="123" url="http://foo.example.org/123">[media]http://foo.example.org/123[/media]</X2></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'x1',
						[
							'host'     => 'example.org',
							'extract'  => "/(?'id'\\d+)/",
							'template' => ''
						]
					);
					$configurator->MediaEmbed->add(
						'x2',
						[
							'host'     => 'foo.example.org',
							'extract'  => "/(?'id'\\d+)/",
							'template' => ''
						]
					);
				}
			],
			[
				// Ensure no bad things(tm) happen when there's no match
				'[media]http://example.org/123[/media]',
				'<t>[media]http://example.org/123[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'x2',
						[
							'host'     => 'foo.example.org',
							'extract'  => "/(?'id'\\d+)/",
							'template' => ''
						]
					);
				}
			],
			[
				// Test that we don't replace the "id" attribute with an URL
				'[media=foo]http://example.org/123[/media]',
				'<r><FOO id="123" url="http://example.org/123">[media=foo]http://example.org/123[/media]</FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'     => 'foo.example.org',
							'extract'  => "/(?'id'\\d+)/",
							'template' => ''
						]
					);
				}
			],
			[
				'[media]http://example.com/baz[/media]',
				'<t>[media]http://example.com/baz[/media]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'     => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'template' => 'foo'
						]
					);
				}
			],
			[
				'[media]http://example.com/foo[/media]',
				'<r><FOO foo="foo" url="http://example.com/foo">[media]http://example.com/foo[/media]</FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'     => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'template' => 'foo'
						]
					);
				}
			],
			[
				// @bar is invalid, no match == tag is invalidated
				'[foo bar=BAR]http://example.com/baz[/foo]',
				'<t>[foo bar=BAR]http://example.com/baz[/foo]</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'     => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'template' => 'foo'
						]
					);
				}
			],
			[
				// No match on URL but @bar is valid == tag is kept
				'[foo bar=bar]http://example.com/baz[/foo]',
				'<r><FOO bar="bar" url="http://example.com/baz"><s>[foo bar=bar]</s>http://example.com/baz<e>[/foo]</e></FOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'foo',
						[
							'host'     => 'example.com',
							'extract'  => [
								"!example\\.com/(?<foo>foo)!",
								"!example\\.com/(?<bar>bar)!"
							],
							'template' => 'foo'
						]
					);
				}
			],
			// =================================================================
			// Bundled sites tests
			// =================================================================
			[
				'http://abcnews.go.com/US/video/missing-malaysian-flight-words-revealed-hunt-continues-hundreds-22880799',
				'<r><ABCNEWS id="22880799" url="http://abcnews.go.com/US/video/missing-malaysian-flight-words-revealed-hunt-continues-hundreds-22880799">http://abcnews.go.com/US/video/missing-malaysian-flight-words-revealed-hunt-continues-hundreds-22880799</ABCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('abcnews');
				}
			],
			[
				'http://abcnews.go.com/Politics/video/special-live-1-14476486',
				'<r><ABCNEWS id="14476486" url="http://abcnews.go.com/Politics/video/special-live-1-14476486">http://abcnews.go.com/Politics/video/special-live-1-14476486</ABCNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('abcnews');
				}
			],
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<r><AMAZON id="B00GQT1LNO" tld="ca" url="http://www.amazon.ca/gp/product/B00GQT1LNO/">http://www.amazon.ca/gp/product/B00GQT1LNO/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.co.jp/gp/product/B003AKZ6I8/',
				'<r><AMAZON id="B003AKZ6I8" tld="jp" url="http://www.amazon.co.jp/gp/product/B003AKZ6I8/">http://www.amazon.co.jp/gp/product/B003AKZ6I8/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.co.uk/gp/product/B00BET0NR6/',
				'<r><AMAZON id="B00BET0NR6" tld="uk" url="http://www.amazon.co.uk/gp/product/B00BET0NR6/">http://www.amazon.co.uk/gp/product/B00BET0NR6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/dp/B002MUC0ZY',
				'<r><AMAZON id="B002MUC0ZY" url="http://www.amazon.com/dp/B002MUC0ZY">http://www.amazon.com/dp/B002MUC0ZY</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/',
				'<r><AMAZON id="B001RB2CXY" url="http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/">http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/gp/product/B0094H8H7I',
				'<r><AMAZON id="B0094H8H7I" url="http://www.amazon.com/gp/product/B0094H8H7I">http://www.amazon.com/gp/product/B0094H8H7I</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/',
				'<r><AMAZON id="B00ET2LTE6" tld="de" url="http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/">http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/',
				'<r><AMAZON id="B005NIKPAY" tld="fr" url="http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/">http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.it/gp/product/B00JGOMIP6/',
				'<r><AMAZON id="B00JGOMIP6" tld="it" url="http://www.amazon.it/gp/product/B00JGOMIP6/">http://www.amazon.it/gp/product/B00JGOMIP6/</AMAZON></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://audioboo.fm/boos/2439994-deadline-day-update',
				'<r><AUDIOBOO id="2439994" url="http://audioboo.fm/boos/2439994-deadline-day-update">http://audioboo.fm/boos/2439994-deadline-day-update</AUDIOBOO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audioboo');
				}
			],
			[
				'http://www.audiomack.com/song/random-2/buy-the-world-final-1',
				'<r><AUDIOMACK id="random-2/buy-the-world-final-1" mode="song" url="http://www.audiomack.com/song/random-2/buy-the-world-final-1">http://www.audiomack.com/song/random-2/buy-the-world-final-1</AUDIOMACK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.audiomack.com/album/hz-global/double-a-side-vol3',
				'<r><AUDIOMACK id="hz-global/double-a-side-vol3" mode="album" url="http://www.audiomack.com/album/hz-global/double-a-side-vol3">http://www.audiomack.com/album/hz-global/double-a-side-vol3</AUDIOMACK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://blip.tv/play/AYKn_x0A',
				'<r><BLIP id="AYKn_x0A" url="http://blip.tv/play/AYKn_x0A">http://blip.tv/play/AYKn_x0A</BLIP></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/play/AYGJ%2BSkC',
				'<r><BLIP id="AYGJ%2BSkC" url="http://blip.tv/play/AYGJ%2BSkC">http://blip.tv/play/AYGJ%2BSkC</BLIP></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/play/AYGJ+SkC',
				'<r><BLIP id="AYGJ+SkC" url="http://blip.tv/play/AYGJ+SkC">http://blip.tv/play/AYGJ+SkC</BLIP></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://www.break.com/video/video-game-playing-frog-wants-more-2278131',
				'<r><BREAK id="2278131" url="http://www.break.com/video/video-game-playing-frog-wants-more-2278131">http://www.break.com/video/video-game-playing-frog-wants-more-2278131</BREAK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('break');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<r><CBSNEWS id="50156501" url="http://www.cbsnews.com/video/watch/?id=50156501n">http://www.cbsnews.com/video/watch/?id=50156501n</CBSNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://video.cnbc.com/gallery/?video=3000269279',
				'<r><CNBC id="3000269279" url="http://video.cnbc.com/gallery/?video=3000269279">http://video.cnbc.com/gallery/?video=3000269279</CNBC></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnbc');
				}
			],
			[
				'http://edition.cnn.com/video/data/2.0/video/showbiz/2013/10/25/spc-preview-savages-stephen-king-thor.cnn.html',
				'<r><CNN id="showbiz/2013/10/25/spc-preview-savages-stephen-king-thor.cnn" url="http://edition.cnn.com/video/data/2.0/video/showbiz/2013/10/25/spc-preview-savages-stephen-king-thor.cnn.html">http://edition.cnn.com/video/data/2.0/video/showbiz/2013/10/25/spc-preview-savages-stephen-king-thor.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
				}
			],
			[
				'http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html',
				'<r><CNN id="bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn" url="http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html">http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
				}
			],
			[
				'http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html',
				'<r><CNN id="bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn" url="http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html">http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://www.cnn.com/video/data/2.0/video/us/2014/09/01/lead-dnt-brown-property-seizures.cnn.html',
				'<r><CNN id="us/2014/09/01/lead-dnt-brown-property-seizures.cnn" url="http://www.cnn.com/video/data/2.0/video/us/2014/09/01/lead-dnt-brown-property-seizures.cnn.html">http://www.cnn.com/video/data/2.0/video/us/2014/09/01/lead-dnt-brown-property-seizures.cnn.html</CNN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/',
				'<r><CNNMONEY id="technology/2014/05/20/t-twitch-vp-on-future.cnnmoney" url="http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/">http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/</CNNMONEY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/',
				'<r><CNNMONEY id="technology/2014/05/20/t-twitch-vp-on-future.cnnmoney" url="http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/">http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/</CNNMONEY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cnn');
					$configurator->MediaEmbed->add('cnnmoney');
				}
			],
			[
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<r><COLLEGEHUMOR id="1181601" url="http://www.collegehumor.com/video/1181601/more-than-friends">http://www.collegehumor.com/video/1181601/more-than-friends</COLLEGEHUMOR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://coub.com/view/6veusoty',
				'<r><COUB id="6veusoty" url="http://coub.com/view/6veusoty">http://coub.com/view/6veusoty</COUB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('coub');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<r><DAILYMOTION id="x222z1" url="http://www.dailymotion.com/video/x222z1">http://www.dailymotion.com/video/x222z1</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/user/Dailymotion/2#video=x222z1',
				'<r><DAILYMOTION id="x222z1" url="http://www.dailymotion.com/user/Dailymotion/2#video=x222z1">http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</DAILYMOTION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.ebay.com/itm/Converse-All-Star-Chuck-Taylor-Black-Hi-Canvas-M9160-Men-/251053262701',
				'<r><EBAY id="251053262701" url="http://www.ebay.com/itm/Converse-All-Star-Chuck-Taylor-Black-Hi-Canvas-M9160-Men-/251053262701">http://www.ebay.com/itm/Converse-All-Star-Chuck-Taylor-Black-Hi-Canvas-M9160-Men-/251053262701</EBAY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://www.ebay.com/itm/251053262701',
				'<r><EBAY id="251053262701" url="http://www.ebay.com/itm/251053262701">http://www.ebay.com/itm/251053262701</EBAY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item=171349018269',
				'<r><EBAY id="171349018269" url="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&amp;item=171349018269">http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&amp;item=171349018269</EBAY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://8tracks.com/midna/2242699',
				'<r><EIGHTTRACKS id="2242699">http://8tracks.com/midna/2242699</EIGHTTRACKS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('eighttracks');
				}
			],
			[
				'http://espn.go.com/video/clip?id=10315344',
				'<r><ESPN cms="espn" id="10315344" url="http://espn.go.com/video/clip?id=10315344">http://espn.go.com/video/clip?id=10315344</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://m.espn.go.com/general/video?vid=10926479',
				'<r><ESPN cms="espn" id="10926479" url="http://m.espn.go.com/general/video?vid=10926479">http://m.espn.go.com/general/video?vid=10926479</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://espndeportes.espn.go.com/videohub/video/clipDeportes?id=2002850',
				'<r><ESPN cms="deportes" id="2002850" url="http://espndeportes.espn.go.com/videohub/video/clipDeportes?id=2002850">http://espndeportes.espn.go.com/videohub/video/clipDeportes?id=2002850</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://espn.go.com/video/clip?id=espn:11195358',
				'<r><ESPN cms="espn" id="11195358" url="http://espn.go.com/video/clip?id=espn:11195358">http://espn.go.com/video/clip?id=espn:11195358</ESPN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<r><FACEBOOK id="10100658170103643" url="https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater">https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/video/video.php?v=10150451523596807',
				'<r><FACEBOOK id="10150451523596807" url="https://www.facebook.com/video/video.php?v=10150451523596807">https://www.facebook.com/video/video.php?v=10150451523596807</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<r><FACEBOOK id="10151471074398553" url="https://www.facebook.com/FacebookDevelopers/posts/10151471074398553">https://www.facebook.com/FacebookDevelopers/posts/10151471074398553</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/photo.php?fbid=10152476416772631',
				'<r><FACEBOOK id="10152476416772631" url="https://www.facebook.com/photo.php?fbid=10152476416772631">https://www.facebook.com/photo.php?fbid=10152476416772631</FACEBOOK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/pages/Bourne-Ultimatum/105742379466221',
				'<t>https://www.facebook.com/pages/Bourne-Ultimatum/105742379466221</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://apps.facebook.com/concertsbybit/facebook/events/8362556/rsvp',
				'<t>https://apps.facebook.com/concertsbybit/facebook/events/8362556/rsvp</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/',
				'<r><FOXNEWS id="3592758613001" url="http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/">http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/</FOXNEWS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('foxnews');
				}
			],
			[
				'http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david',
				'<r><FUNNYORDIE id="bf313bd8b4" url="http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david">http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david</FUNNYORDIE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('funnyordie');
				}
			],
			[
				'http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/',
				'<r><GAMESPOT id="6415176" url="http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/">http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/',
				'<r><GAMESPOT id="6412922" url="http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/">http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/',
				'<r><GAMESPOT id="6414307" url="http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/">http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/</GAMESPOT></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<r><GIST id="s9e/6806305" url="https://gist.github.com/s9e/6806305">https://gist.github.com/s9e/6806305</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/6806305',
				'<r><GIST id="6806305" url="https://gist.github.com/6806305">https://gist.github.com/6806305</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599',
				'<r><GIST id="s9e/6806305/ad88d904b082c8211afa040162402015aacb8599" url="https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599">https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599</GIST></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'http://www.gofundme.com/2p37ao',
				'<r><GOFUNDME id="2p37ao" url="http://www.gofundme.com/2p37ao">http://www.gofundme.com/2p37ao</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/2p37ao#',
				'<r><GOFUNDME id="2p37ao" url="http://www.gofundme.com/2p37ao">http://www.gofundme.com/2p37ao#</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/2p37ao?pc=trend',
				'<r><GOFUNDME id="2p37ao" url="http://www.gofundme.com/2p37ao?pc=trend">http://www.gofundme.com/2p37ao?pc=trend</GOFUNDME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'http://www.gofundme.com/tour/',
				'<t>http://www.gofundme.com/tour/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gofundme');
				}
			],
			[
				'https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW',
				'<r><GOOGLEPLUS oid="110286587261352351537" pid="XMABm8rLvRW" url="https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW">https://plus.google.com/110286587261352351537/posts/XMABm8rLvRW</GOOGLEPLUS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googleplus');
				}
			],
			[
				'https://docs.google.com/spreadsheets/d/1f988o68HDvk335xXllJD16vxLBuRcmm3vg6U9lVaYpA',
				'<r><GOOGLESHEETS id="1f988o68HDvk335xXllJD16vxLBuRcmm3vg6U9lVaYpA" url="https://docs.google.com/spreadsheets/d/1f988o68HDvk335xXllJD16vxLBuRcmm3vg6U9lVaYpA">https://docs.google.com/spreadsheets/d/1f988o68HDvk335xXllJD16vxLBuRcmm3vg6U9lVaYpA</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheet/ccc?key=0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE&usp=sharing',
				'<r><GOOGLESHEETS id="0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE" url="https://docs.google.com/spreadsheet/ccc?key=0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE&amp;usp=sharing">https://docs.google.com/spreadsheet/ccc?key=0AnfAFqEAnlFvdG5IMDdnd0xZQUlxZkdxbzg5SGZJQlE&amp;usp=sharing</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70',
				'<r><GOOGLESHEETS gid="70" id="0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc" url="https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70">https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70</GOOGLESHEETS></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('googlesheets');
				}
			],
			[
				'http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761',
				'<r><GROOVESHARK playlistid="74854761" url="http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761">http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761</GROOVESHARK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761',
				'<r><GROOVESHARK playlistid="74854761" url="http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761">http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761</GROOVESHARK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<r><IGN id="http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer" url="http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer">http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer</IGN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'http://www.imdb.com/video/epk/vi387296537/',
				'<r><IMDB id="387296537" url="http://www.imdb.com/video/epk/vi387296537/">http://www.imdb.com/video/epk/vi387296537/</IMDB></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imdb');
				}
			],
			[
				'https://imgur.com/a/9UGCL',
				'<r><IMGUR id="9UGCL" url="https://imgur.com/a/9UGCL">https://imgur.com/a/9UGCL</IMGUR></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('imgur');
				}
			],
			[
				'http://www.indiegogo.com/projects/513633',
				'<r><INDIEGOGO id="513633" url="http://www.indiegogo.com/projects/513633">http://www.indiegogo.com/projects/513633</INDIEGOGO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://instagram.com/p/gbGaIXBQbn/',
				'<r><INSTAGRAM id="gbGaIXBQbn" url="http://instagram.com/p/gbGaIXBQbn/">http://instagram.com/p/gbGaIXBQbn/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://instagram.com/p/lx39ciHzD_/',
				'<r><INSTAGRAM id="lx39ciHzD_" url="http://instagram.com/p/lx39ciHzD_/">http://instagram.com/p/lx39ciHzD_/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://instagram.com/p/k28LE0Dte-/',
				'<r><INSTAGRAM id="k28LE0Dte-" url="http://instagram.com/p/k28LE0Dte-/">http://instagram.com/p/k28LE0Dte-/</INSTAGRAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('instagram');
				}
			],
			[
				'http://www.izlesene.com/video/lily-allen-url-badman/7600704',
				'<r><IZLESENE id="7600704" url="http://www.izlesene.com/video/lily-allen-url-badman/7600704">http://www.izlesene.com/video/lily-allen-url-badman/7600704</IZLESENE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('izlesene');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<r><KICKSTARTER id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html',
				'<r><KICKSTARTER card="card" id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<r><KICKSTARTER id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html" video="video">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html</KICKSTARTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<r><LIVELEAK id="3dd_1366238099" url="http://www.liveleak.com/view?i=3dd_1366238099">http://www.liveleak.com/view?i=3dd_1366238099</LIVELEAK></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<r><METACAFE id="10785282" url="http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/">http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</METACAFE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/',
				'<r><MIXCLOUD id="OneTakeTapes/timsch-one-take-tapes-2" url="http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/">http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/',
				'<r><MIXCLOUD id="s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg" url="https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/">https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg/',
				'<r><MIXCLOUD id="s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg" url="https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-f%C3%BCr-kil-seine-party-liebe-gr%C3%BC%C3%9Fe-aus-freiburg/">https://www.mixcloud.com/s2ck/dj-miiiiiit-mustern-drauf-guestmix-für-kil-seine-party-liebe-grüße-aus-freiburg/</MIXCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2&foo=1/',
				'<t>http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2&amp;foo=1/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/categories/classical/',
				'<t>http://www.mixcloud.com/categories/classical/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.mixcloud.com/tag/npr/',
				'<t>http://www.mixcloud.com/tag/npr/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('mixcloud');
				}
			],
			[
				'http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html',
				'<r><NYTIMES id="100000002907606" url="http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html">http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html</NYTIMES></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nytimes');
				}
			],
			[
				'http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html',
				'<r><NYTIMES id="100000001950744" url="http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html">http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html</NYTIMES></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('nytimes');
				}
			],
			[
				'http://pastebin.com/9jEf44nc',
				'<r><PASTEBIN id="9jEf44nc" url="http://pastebin.com/9jEf44nc">http://pastebin.com/9jEf44nc</PASTEBIN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'http://pastebin.com/raw.php?i=9jEf44nc',
				'<r><PASTEBIN id="9jEf44nc" url="http://pastebin.com/raw.php?i=9jEf44nc">http://pastebin.com/raw.php?i=9jEf44nc</PASTEBIN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('pastebin');
				}
			],
			[
				'http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/',
				'<r><PREZI id="5ye8po_hmikp" url="http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/">http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/</PREZI></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://blog.prezi.com/latest/2014/2/7/10-most-common-rookie-mistakes-in-public-speaking.html/',
				'<t>http://blog.prezi.com/latest/2014/2/7/10-most-common-rookie-mistakes-in-public-speaking.html/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://prezi.com/explore/staff-picks/',
				'<t>http://prezi.com/explore/staff-picks/</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('prezi');
				}
			],
			[
				'http://rd.io/x/QcD7oTdeWevg/',
				'<r><RDIO id="QcD7oTdeWevg" url="http://rd.io/x/QcD7oTdeWevg/">http://rd.io/x/QcD7oTdeWevg/</RDIO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rdio');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<r><RUTUBE id="4118278" url="http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd">http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd</RUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.scribd.com/doc/233658242/Detect-Malware-w-Memory-Forensics',
				'<r><SCRIBD id="233658242" url="http://www.scribd.com/doc/233658242/Detect-Malware-w-Memory-Forensics">http://www.scribd.com/doc/233658242/Detect-Malware-w-Memory-Forensics</SCRIBD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('scribd');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<r><SLIDESHARE id="23431564" url="http://www.slideshare.net/Slideshare/how-23431564">http://www.slideshare.net/Slideshare/how-23431564</SLIDESHARE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<r><SOUNDCLOUD id="http://api.soundcloud.com/tracks/98282116" url="http://api.soundcloud.com/tracks/98282116">[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'[soundcloud url="https://api.soundcloud.com/tracks/12345?secret_token=s-foobar" width="100%" height="166" iframe="true" /]',
				'<r><SOUNDCLOUD id="https://api.soundcloud.com/tracks/12345?secret_token=s-foobar" secret_token="s-foobar" url="https://api.soundcloud.com/tracks/12345?secret_token=s-foobar">[soundcloud url="https://api.soundcloud.com/tracks/12345?secret_token=s-foobar" width="100%" height="166" iframe="true" /]</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/andrewbird/three-white-horses',
				'<r><SOUNDCLOUD id="https://soundcloud.com/andrewbird/three-white-horses" url="https://soundcloud.com/andrewbird/three-white-horses">https://soundcloud.com/andrewbird/three-white-horses</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/',
				'<r><SOUNDCLOUD id="https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix" url="https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/">https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'[soundcloud url="https://api.soundcloud.com/playlists/1919974" width="100%" height="450" iframe="true" /]',
				'<r><SOUNDCLOUD id="https://api.soundcloud.com/playlists/1919974" url="https://api.soundcloud.com/playlists/1919974">[soundcloud url="https://api.soundcloud.com/playlists/1919974" width="100%" height="450" iframe="true" /]</SOUNDCLOUD></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'spotify:track:5JunxkcjfCYcY7xJ29tLai',
				'<r><SPOTIFY uri="spotify:track:5JunxkcjfCYcY7xJ29tLai">spotify:track:5JunxkcjfCYcY7xJ29tLai</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'spotify:album:0coYJwk0uFvOXDkgMzQJMG',
				'<r><SPOTIFY uri="spotify:album:0coYJwk0uFvOXDkgMzQJMG">spotify:album:0coYJwk0uFvOXDkgMzQJMG</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'[spotify]spotify:trackset:PREFEREDTITLE:5Z7ygHQo02SUrFmcgpwsKW,1x6ACsKV4UdWS2FMuPFUiT,4bi73jCM02fMpkI11Lqmfe[/spotify]',
				'<r><SPOTIFY uri="spotify:trackset:PREFEREDTITLE:5Z7ygHQo02SUrFmcgpwsKW,1x6ACsKV4UdWS2FMuPFUiT,4bi73jCM02fMpkI11Lqmfe"><s>[spotify]</s>spotify:trackset:PREFEREDTITLE:5Z7ygHQo02SUrFmcgpwsKW,1x6ACsKV4UdWS2FMuPFUiT,4bi73jCM02fMpkI11Lqmfe<e>[/spotify]</e></SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'http://open.spotify.com/user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt',
				'<r><SPOTIFY path="user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt" url="http://open.spotify.com/user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt">http://open.spotify.com/user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ',
				'<r><SPOTIFY path="track/6acKqVtKngFXApjvXsU6mQ" url="https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ">https://play.spotify.com/track/6acKqVtKngFXApjvXsU6mQ</SPOTIFY></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'http://strawpoll.me/738091',
				'<r><STRAWPOLL id="738091" url="http://strawpoll.me/738091">http://strawpoll.me/738091</STRAWPOLL></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('strawpoll');
				}
			],
			[
				'http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien',
				'<r><TEAMCOCO id="73784" url="http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien">http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien</TEAMCOCO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('teamcoco');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<r><TED id="talks/eli_pariser_beware_online_filter_bubbles.html" url="http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html">http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html</TED></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/',
				'<r><THEATLANTIC id="358928" url="http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/">http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/</THEATLANTIC></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('theatlantic');
				}
			],
			[
				'http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/',
				'<r><THEONION id="35998" url="http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/">http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/</THEONION></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('theonion');
				}
			],
			[
				'http://tinypic.com/player.php?v=29x86j9&s=8',
				'<r><TINYPIC id="29x86j9" s="8" url="http://tinypic.com/player.php?v=29x86j9&amp;s=8">http://tinypic.com/player.php?v=29x86j9&amp;s=8</TINYPIC></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('tinypic');
				}
			],
			[
				'http://www.traileraddict.com/tags/musical',
				'<t>http://www.traileraddict.com/tags/musical</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('traileraddict');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<r><TWITCH channel="minigolf2000" url="http://www.twitch.tv/minigolf2000">http://www.twitch.tv/minigolf2000</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/c/2475925',
				'<r><TWITCH channel="minigolf2000" chapter_id="2475925" url="http://www.twitch.tv/minigolf2000/c/2475925">http://www.twitch.tv/minigolf2000/c/2475925</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/497929990',
				'<r><TWITCH archive_id="497929990" channel="minigolf2000" url="http://www.twitch.tv/minigolf2000/b/497929990">http://www.twitch.tv/minigolf2000/b/497929990</TWITCH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://twitter.com/BarackObama/statuses/266031293945503744',
				'<r><TWITTER id="266031293945503744" url="https://twitter.com/BarackObama/statuses/266031293945503744">https://twitter.com/BarackObama/statuses/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/BarackObama/status/266031293945503744',
				'<r><TWITTER id="266031293945503744" url="https://twitter.com/BarackObama/status/266031293945503744">https://twitter.com/BarackObama/status/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'https://twitter.com/#!/BarackObama/status/266031293945503744',
				'<r><TWITTER id="266031293945503744" url="https://twitter.com/#!/BarackObama/status/266031293945503744">https://twitter.com/#!/BarackObama/status/266031293945503744</TWITTER></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'http://www.vevo.com/watch/USUV71400682',
				'<r><VEVO id="USUV71400682" url="http://www.vevo.com/watch/USUV71400682">http://www.vevo.com/watch/USUV71400682</VEVO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vevo');
				}
			],
			[
				'http://www.vevo.com/watch/eminem/the-monster-explicit/USUV71302925',
				'<r><VEVO id="USUV71302925" url="http://www.vevo.com/watch/eminem/the-monster-explicit/USUV71302925">http://www.vevo.com/watch/eminem/the-monster-explicit/USUV71302925</VEVO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vevo');
				}
			],
			[
				'http://www.viagame.com/channels/hearthstone-championship/404917',
				'<r><VIAGAME id="404917" url="http://www.viagame.com/channels/hearthstone-championship/404917">http://www.viagame.com/channels/hearthstone-championship/404917</VIAGAME></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('viagame');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<r><VIMEO id="67207222" url="http://vimeo.com/67207222">http://vimeo.com/67207222</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'http://vube.com/William+Wei/best-drummer-ever-hd/Y8NUZ69Tf7?t=s',
				'<r><VUBE id="Y8NUZ69Tf7" url="http://vube.com/William+Wei/best-drummer-ever-hd/Y8NUZ69Tf7?t=s">http://vube.com/William+Wei/best-drummer-ever-hd/Y8NUZ69Tf7?t=s</VUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vube');
				}
			],
			[
				'http://www.ustream.tv/recorded/40771396',
				'<r><USTREAM url="http://www.ustream.tv/recorded/40771396" vid="40771396">http://www.ustream.tv/recorded/40771396</USTREAM></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://www.ustream.tv/explore/education',
				'<t>http://www.ustream.tv/explore/education</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://www.ustream.tv/upcoming',
				'<t>http://www.ustream.tv/upcoming</t>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://vimeo.com/channels/staffpicks/67207222',
				'<r><VIMEO id="67207222" url="http://vimeo.com/channels/staffpicks/67207222">http://vimeo.com/channels/staffpicks/67207222</VIMEO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vine.co/v/bYwPIluIipH',
				'<r><VINE id="bYwPIluIipH" url="https://vine.co/v/bYwPIluIipH">https://vine.co/v/bYwPIluIipH</VINE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vine');
				}
			],
			[
				'http://vocaroo.com/i/s0dRy3rZ47bf',
				'<r><VOCAROO id="s0dRy3rZ47bf" url="http://vocaroo.com/i/s0dRy3rZ47bf">http://vocaroo.com/i/s0dRy3rZ47bf</VOCAROO></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vocaroo');
				}
			],
			[
				'http://www.worldstarhiphop.com/featured/71630',
				'<r><WSHH id="71630" url="http://www.worldstarhiphop.com/featured/71630">http://www.worldstarhiphop.com/featured/71630</WSHH></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2',
				'<r><WSJ id="09FB2B3B-583E-4284-99D8-FEF6C23BE4E2" url="http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2">http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html',
				'<r><WSJ id="9B3DF790-9D20-442C-B564-51524B06FD26" url="http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html">http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8',
				'<r><WSJ id="3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8" url="http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8">http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8</WSJ></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'https://screen.yahoo.com/mr-short-term-memory-000000263.html',
				'<r><YAHOOSCREEN id="mr-short-term-memory-000000263" url="https://screen.yahoo.com/mr-short-term-memory-000000263.html">https://screen.yahoo.com/mr-short-term-memory-000000263.html</YAHOOSCREEN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('yahooscreen');
				}
			],
			[
				'https://screen.yahoo.com/dana-carvey-snl-skits/church-chat-satan-000000502.html',
				'<r><YAHOOSCREEN id="church-chat-satan-000000502" url="https://screen.yahoo.com/dana-carvey-snl-skits/church-chat-satan-000000502.html">https://screen.yahoo.com/dana-carvey-snl-skits/church-chat-satan-000000502.html</YAHOOSCREEN></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('yahooscreen');
				}
			],
			[
				'http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html',
				'<r><YOUKU id="XNzQwNjcxNDM2" url="http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html">http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html</YOUKU></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youku');
				}
			],
			[
				'[media=youtube]-cEzsCAzTak[/media]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="-cEzsCAzTak">[media=youtube]-cEzsCAzTak[/media]</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel">[media]http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel[/media]</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="-cEzsCAzTak"><s>[YOUTUBE]</s>-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel"><s>[YOUTUBE]</s>http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?feature=player_embedded&v=-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak"><s>[YOUTUBE]</s>http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/v/-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/v/-cEzsCAzTak"><s>[YOUTUBE]</s>http://www.youtube.com/v/-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://youtu.be/-cEzsCAzTak[/YOUTUBE]',
				'<r><YOUTUBE id="-cEzsCAzTak" url="http://youtu.be/-cEzsCAzTak"><s>[YOUTUBE]</s>http://youtu.be/-cEzsCAzTak<e>[/YOUTUBE]</e></YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'<r>Check this: <YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</YOUTUBE> and that: <URL url="http://example.com">http://example.com</URL></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0#t=113',
				'<r><YOUTUBE id="9bZkp7q19f0" t="113" url="http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0#t=113">http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0#t=113</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0&t=113',
				'<r><YOUTUBE id="9bZkp7q19f0" t="113" url="http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0&amp;t=113">http://www.youtube.com/watch?feature=player_detailpage&amp;v=9bZkp7q19f0&amp;t=113</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=1h23m45s',
				'<r><YOUTUBE h="1" id="wZZ7oFKsKzY" m="23" s="45" url="http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=1h23m45s">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=1h23m45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=23m45s',
				'<r><YOUTUBE id="wZZ7oFKsKzY" m="23" s="45" url="http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=23m45s">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=23m45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=45s',
				'<r><YOUTUBE id="wZZ7oFKsKzY" t="45" url="http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=45s">http://www.youtube.com/watch?v=wZZ7oFKsKzY&amp;t=45s</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA',
				'<r><YOUTUBE id="pC35x6iIPmo" list="PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA" url="http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA">http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123',
				'<r><YOUTUBE id="pC35x6iIPmo" list="PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA" t="123" url="http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123">http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch_popup?v=qybUFnY7Y8w',
				'<r><YOUTUBE id="qybUFnY7Y8w" url="http://www.youtube.com/watch_popup?v=qybUFnY7Y8w">http://www.youtube.com/watch_popup?v=qybUFnY7Y8w</YOUTUBE></r>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-ca.amazon.ca/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B00GQT1LNO&amp;o=15&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.ca/gp/product/B00GQT1LNO/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-ca.amazon.ca/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B00GQT1LNO&amp;o=15&amp;t=foo-20"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
					$configurator->rendering->parameters['AMAZON_ASSOCIATE_TAG'] = 'foo-20';
				}
			],
			[
				'http://www.amazon.co.jp/gp/product/B003AKZ6I8/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-jp.amazon.co.jp/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B003AKZ6I8&amp;o=9&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.co.uk/gp/product/B00BET0NR6/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-uk.amazon.co.uk/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B00BET0NR6&amp;o=2&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.com/dp/B002MUC0ZY',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm.amazon.com/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B002MUC0ZY&amp;o=1&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-de.amazon.de/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B00ET2LTE6&amp;o=3&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-fr.amazon.fr/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B005NIKPAY&amp;o=8&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.amazon.it/gp/product/B00JGOMIP6/',
				'<iframe width="120" height="240" allowfullscreen="" frameborder="0" scrolling="no" src="//rcm-it.amazon.it/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins=B00JGOMIP6&amp;o=29&amp;t=_"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('amazon');
				}
			],
			[
				'http://www.audiomack.com/album/hz-global/double-a-side-vol3',
				'<iframe width="100%" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" height="352" src="//www.audiomack.com/embed3-album/hz-global/double-a-side-vol3"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.audiomack.com/song/random-2/buy-the-world-final-1',
				'<iframe width="100%" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" height="144" src="//www.audiomack.com/embed3/random-2/buy-the-world-final-1"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('audiomack');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="425" height="279" data="http://i.i.cbsi.com/cnwk.1d/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="si=254&amp;contentValue=50156501"><embed type="application/x-shockwave-flash" width="425" height="279" allowfullscreen="" src="http://i.i.cbsi.com/cnwk.1d/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf" flashvars="si=254&amp;contentValue=50156501"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<iframe width="600" height="369" src="//www.collegehumor.com/e/1181601" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<iframe width="560" height="315" src="//www.dailymotion.com/embed/video/x222z1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.ebay.com/itm/Converse-All-Star-Chuck-Taylor-Black-Hi-Canvas-M9160-Men-/251053262701',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="355" height="300" data="http://togo.ebay.com/togo/togo.swf?2008013100"><param name="allowfullscreen" value="true"><param name="flashvars" value="base=http://togo.ebay.com/togo/&amp;mode=normal&amp;itemid=251053262701"><embed type="application/x-shockwave-flash" src="http://togo.ebay.com/togo/togo.swf?2008013100" width="355" height="300" allowfullscreen="" flashvars="base=http://togo.ebay.com/togo/&amp;mode=normal&amp;itemid=251053262701"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ebay');
				}
			],
			[
				'http://espn.go.com/video/clip?id=10315344',
				'<iframe width="560" height="315" src="https://espn.go.com/video/iframe/twitter/?cms=espn&amp;id=10315344" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'http://espndeportes.espn.go.com/videohub/video/clipDeportes?id=2002850',
				'<iframe width="560" height="315" src="https://espn.go.com/video/iframe/twitter/?cms=deportes&amp;id=2002850" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'https://www.facebook.com/video/video.php?v=10100658170103643',
				'<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#10100658170103643" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#10151471074398553" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david',
				'<iframe width="640" height="360" src="http://www.funnyordie.com/embed/bf313bd8b4" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('funnyordie');
				}
			],
			[
				'http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/',
				'<iframe width="640" height="400" src="//www.gamespot.com/videos/embed/6415176/" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<iframe style="width:100%" src="//s9e.github.io/iframe/gist.min.html#s9e/6806305" frameborder="0" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="400" height="400" data="//grooveshark.com/widget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID=74854761&amp;songID="><embed type="application/x-shockwave-flash" src="//grooveshark.com/widget.swf" width="400" height="400" allowfullscreen="" flashvars="playlistID=74854761&amp;songID="></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<iframe width="468" height="263" src="http://widgets.ign.com/video/embed/content.html?url=http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'http://www.indiegogo.com/projects/513633',
				'<iframe width="224" height="486" src="//www.indiegogo.com/project/513633/widget" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<iframe width="220" height="380" src="//www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<iframe width="480" height="360" src="//www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<iframe width="640" height="360" src="http://www.liveleak.com/ll_embed?i=3dd_1366238099" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<iframe width="560" height="315" src="//www.metacafe.com/embed/10785282/" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<iframe width="720" height="405" src="//rutube.ru/play/embed/4118278" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<iframe width="427" height="356" src="//www.slideshare.net/slideshow/embed_code/23431564" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=http://api.soundcloud.com/tracks/98282116"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'[soundcloud url="https://api.soundcloud.com/tracks/12345?secret_token=s-foobar" width="100%" height="166" iframe="true" /]',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/12345?secret_token=s-foobar&amp;secret_token=s-foobar"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/andrewbird/three-white-horses',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://soundcloud.com/andrewbird/three-white-horses"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'[soundcloud url="https://api.soundcloud.com/playlists/1919974" width="100%" height="450" iframe="true" /]',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/1919974"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				// http://xenforo.com/community/threads/s9e-media-bbcodes-pack.61883/page-16#post-741750
				'[media=soundcloud]nruau/nruau-mix2[/media]',
				'<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=https://soundcloud.com/nruau/nruau-mix2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'[spotify]spotify:track:5JunxkcjfCYcY7xJ29tLai[/spotify]',
				'<iframe width="400" height="480" allowfullscreen="" frameborder="0" scrolling="no" src="https://embed.spotify.com/?view=coverart&amp;uri=spotify:track:5JunxkcjfCYcY7xJ29tLai"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://play.spotify.com/album/5OSzFvFAYuRh93WDNCTLEz',
				'<iframe width="400" height="480" allowfullscreen="" frameborder="0" scrolling="no" src="https://embed.spotify.com/?view=coverart&amp;uri=spotify:album:5OSzFvFAYuRh93WDNCTLEz"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'https://play.spotify.com/track/3lDpjvbifbmrmzWGE8F9zd',
				'<iframe width="400" height="480" allowfullscreen="" frameborder="0" scrolling="no" src="https://embed.spotify.com/?view=coverart&amp;uri=spotify:track:3lDpjvbifbmrmzWGE8F9zd"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('spotify');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="http://embed.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.ted.com/talks/richard_ledgett_the_nsa_responds_to_edward_snowden_s_ted_talk',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="http://embed.ted.com/talks/richard_ledgett_the_nsa_responds_to_edward_snowden_s_ted_talk.html"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="//www.twitch.tv/widgets/live_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=minigolf2000&amp;auto_play=false"><embed type="application/x-shockwave-flash" width="620" height="378" allowfullscreen="" src="//www.twitch.tv/widgets/live_embed_player.swf" flashvars="channel=minigolf2000&amp;auto_play=false"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/amazhs/c/4493103',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="//www.twitch.tv/widgets/archive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=amazhs&amp;chapter_id=4493103&amp;auto_play=false"><embed type="application/x-shockwave-flash" width="620" height="378" allowfullscreen="" src="//www.twitch.tv/widgets/archive_embed_player.swf" flashvars="channel=amazhs&amp;chapter_id=4493103&amp;auto_play=false"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/497929990',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="//www.twitch.tv/widgets/archive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=minigolf2000&amp;archive_id=497929990&amp;auto_play=false"><embed type="application/x-shockwave-flash" width="620" height="378" allowfullscreen="" src="//www.twitch.tv/widgets/archive_embed_player.swf" flashvars="channel=minigolf2000&amp;archive_id=497929990&amp;auto_play=false"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'https://twitter.com/BarackObama/statuses/266031293945503744',
				'<iframe width="500" height="186" src="//s9e.github.io/iframe/twitter.min.html#266031293945503744" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitter');
				}
			],
			[
				'http://www.ustream.tv/recorded/40771396',
				'<iframe width="480" height="302" allowfullscreen="" frameborder="0" scrolling="no" src="//www.ustream.tv/embed/recorded/40771396"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ustream');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<iframe width="560" height="315" src="//player.vimeo.com/video/67207222" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vine.co/v/bYwPIluIipH',
				'<iframe width="480" height="480" src="https://vine.co/v/bYwPIluIipH/embed/simple" allowfullscreen="" frameborder="0" scrolling="no"></iframe><script async="" src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vine');
				}
			],
			[
				'http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2',
				'<iframe width="512" height="288" src="http://live.wsj.com/public/page/embed-09FB2B3B_583E_4284_99D8_FEF6C23BE4E2.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wsj');
				}
			],
			[
				'[media=youtube]-cEzsCAzTak[/media]',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE=http://www.youtube.com/watch?v=-cEzsCAzTak]Hi!',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>Hi!',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak',
				'Check this: <iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'Check this: <iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe> and that: <a href="http://example.com">http://example.com</a>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?feature=player_detailpage&v=9bZkp7q19f0#t=113',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/9bZkp7q19f0?controls=2&amp;start=113"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/pC35x6iIPmo?controls=2&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=pC35x6iIPmo&list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA#t=123',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/pC35x6iIPmo?controls=2&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA&amp;start=123"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=1h23m45s',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/wZZ7oFKsKzY?controls=2&amp;start=5025"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'http://www.youtube.com/watch?v=wZZ7oFKsKzY&t=23m45s',
				'<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/wZZ7oFKsKzY?controls=2&amp;start=1425"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}
}