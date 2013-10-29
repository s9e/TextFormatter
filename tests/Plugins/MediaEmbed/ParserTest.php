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

		foreach ($entries as $url => $content)
		{
			file_put_contents(
				'compress.zlib://' . $cacheDir . '/http.' . crc32($url) . '.gz',
				$content
			);
		}

		return $cacheDir;
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
				// Ensure that non-HTTP URLs don't get scraped
				'[media]invalid://example.org/123[/media]',
				'<pt>[media]invalid://example.org/123[/media]</pt>',
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
				'<pt>[media]http://example.invalid/123?x"&gt; foo="bar[/media]</pt>',
				['captureURLs' => false],
				function ($configurator)
				{
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
				'<pt>[media]http://example.invalid/123[/media]</pt>',
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
				'<rt><EXAMPLE id="12" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></rt>',
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
				// Multiple "match" in scrape
				'http://example.invalid/123',
				'<rt><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></rt>',
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
				'<rt><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></rt>',
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
				'<rt><EXAMPLE id="456" url="http://example.invalid/123">http://example.invalid/123</EXAMPLE></rt>',
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
			[
				'[media]http://foo.example.org/123[/media]',
				'<rt><X2 id="123" url="http://foo.example.org/123">[media]http://foo.example.org/123[/media]</X2></rt>',
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
		];
	}

	/**
	* @testdox Scraping tests
	* @dataProvider getScrapingTests
	* @group needs-network
	*/
	public function testScraping()
	{
		call_user_func_array([$this, 'testParsing'], func_get_args());
	}

	public function getScrapingTests()
	{
		return [
			[
				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
				'<rt><BANDCAMP album_id="1122163921" url="http://proleter.bandcamp.com/album/curses-from-past-times-ep">http://proleter.bandcamp.com/album/curses-from-past-times-ep</BANDCAMP></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://proleter.bandcamp.com/track/muhammad-ali',
				'<rt><BANDCAMP album_id="1122163921" track_num="7" url="http://proleter.bandcamp.com/track/muhammad-ali">http://proleter.bandcamp.com/track/muhammad-ali</BANDCAMP></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725',
				'<rt><BLIP id="AYOW3REC" url="http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725">http://blip.tv/hilah-cooking/hilah-cooking-vegetable-beef-stew-6663725</BLIP></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/c18booktracker/full-text-search-quirks-in-google-books-2260037',
				'<rt><BLIP id="AYGJ%2BSkC" url="http://blip.tv/c18booktracker/full-text-search-quirks-in-google-books-2260037">http://blip.tv/c18booktracker/full-text-search-quirks-in-google-books-2260037</BLIP></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://www.colbertnation.com/the-colbert-report-videos/429637/october-14-2013/5-x-five---colbert-moments--under-the-desk',
				'<rt><COLBERTNATION id="mgid:cms:video:colbertnation.com:429637" url="http://www.colbertnation.com/the-colbert-report-videos/429637/october-14-2013/5-x-five---colbert-moments--under-the-desk">http://www.colbertnation.com/the-colbert-report-videos/429637/october-14-2013/5-x-five---colbert-moments--under-the-desk</COLBERTNATION></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('colbertnation');
				}
			],
			[
				'http://www.colbertnation.com/the-colbert-report-collections/429799/sorry--technical-difficulties/',
				'<rt><COLBERTNATION id="mgid:cms:video:colbertnation.com:427533" url="http://www.colbertnation.com/the-colbert-report-collections/429799/sorry--technical-difficulties/">http://www.colbertnation.com/the-colbert-report-collections/429799/sorry--technical-difficulties/</COLBERTNATION></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('colbertnation');
				}
			],
			[
				'http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
				'<rt><COMEDYCENTRAL id="mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea" url="http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats">http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats</COMEDYCENTRAL></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('comedycentral');
				}
			],
			[
				'http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508',
				'<rt><DAILYSHOW id="mgid:cms:video:thedailyshow.com:429537" url="http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508">http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508</DAILYSHOW></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-',
				'<rt><DAILYSHOW id="mgid:cms:video:thedailyshow.com:416478" url="http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-">http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-</DAILYSHOW></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://grooveshark.com/s/Soul+Below/4zGL7i?src=5',
				'<rt><GROOVESHARK songid="35292216" url="http://grooveshark.com/s/Soul+Below/4zGL7i?src=5">http://grooveshark.com/s/Soul+Below/4zGL7i?src=5</GROOVESHARK></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5',
				'<rt><GROOVESHARK songid="35292216" url="http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5">http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5</GROOVESHARK></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://www.hulu.com/watch/445716',
				'<rt><HULU id="lbxMKBY8oOd3pvOBhM8lqQ" url="http://www.hulu.com/watch/445716">http://www.hulu.com/watch/445716</HULU></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('hulu');
				}
			],
			[
				'http://www.indiegogo.com/projects/gameheart-redesigned',
				'<rt><INDIEGOGO id="513633" url="http://www.indiegogo.com/projects/gameheart-redesigned">http://www.indiegogo.com/projects/gameheart-redesigned</INDIEGOGO></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.indiegogo.com/projects/5050-years-a-documentary',
				'<rt><INDIEGOGO id="535215" url="http://www.indiegogo.com/projects/5050-years-a-documentary">http://www.indiegogo.com/projects/5050-years-a-documentary</INDIEGOGO></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/',
				'<rt><RUTUBE id="6613980" url="http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/">http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/</RUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites',
				'<rt><SLIDESHARE id="21112125" url="http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites">http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites</SLIDESHARE></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				'http://teamcoco.com/video/conan-highlight-gigolos-mug-hunt',
				'<rt><TEAMCOCO id="54003" url="http://teamcoco.com/video/conan-highlight-gigolos-mug-hunt">http://teamcoco.com/video/conan-highlight-gigolos-mug-hunt</TEAMCOCO></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('teamcoco');
				}
			],
			[
				'http://www.twitch.tv/m/57217',
				'<rt><TWITCH archive_id="435873548" channel="wcs_america" url="http://www.twitch.tv/m/57217">http://www.twitch.tv/m/57217</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://vk.com/video-7016284_163645555',
				'<rt><VK hash="eb5d7a5e6e1d8b71" oid="-7016284" url="http://vk.com/video-7016284_163645555" vid="163645555">http://vk.com/video-7016284_163645555</VK></rt>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('vk');
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
		call_user_func_array([$this, 'testRendering'], func_get_args());
	}

	public function getScrapingRenderingTests()
	{
		return [
			[
				'http://proleter.bandcamp.com/album/curses-from-past-times-ep',
				'<iframe width="400" height="120" allowfullscreen="" frameborder="0" scrolling="no" src="http://bandcamp.com/EmbeddedPlayer/album=1122163921/size=medium"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://proleter.bandcamp.com/track/muhammad-ali',
				'<iframe width="400" height="42" allowfullscreen="" frameborder="0" scrolling="no" src="http://bandcamp.com/EmbeddedPlayer/album=1122163921/size=small/t=7"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('bandcamp');
				}
			],
			[
				'http://www.colbertnation.com/the-colbert-report-videos/429637/october-14-2013/5-x-five---colbert-moments--under-the-desk',
				'<iframe width="512" height="288" src="http://media.mtvnservices.com/embed/mgid:cms:video:colbertnation.com:429637" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('colbertnation');
				}
			],
			[
				'http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats',
				'<iframe width="512" height="288" src="http://media.mtvnservices.com/embed/mgid:arc:video:comedycentral.com:bc275e2f-48e3-46d9-b095-0254381497ea" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('comedycentral');
				}
			],
			[
				'http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508',
				'<iframe width="512" height="288" src="http://media.mtvnservices.com/embed/mgid:cms:video:thedailyshow.com:429537" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('dailyshow');
				}
			],
			[
				'http://grooveshark.com/s/Soul+Below/4zGL7i?src=5',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="250" height="40" data="http://grooveshark.com/songWidget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID=&amp;songID=35292216"><embed type="application/x-shockwave-flash" src="http://grooveshark.com/songWidget.swf" width="250" height="40" allowfullscreen="" flashvars="playlistID=&amp;songID=35292216"></object>',
				[],
				function ($configurator)
				{
					$configurator->registeredVars['cacheDir'] = __DIR__ . '/../../.cache';
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
		];
	}

	public function getParsingTests()
	{
		return [
			[
				'http://blip.tv/play/AYKn_x0A',
				'<rt><BLIP id="AYKn_x0A" url="http://blip.tv/play/AYKn_x0A">http://blip.tv/play/AYKn_x0A</BLIP></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/play/AYGJ%2BSkC',
				'<rt><BLIP id="AYGJ%2BSkC" url="http://blip.tv/play/AYGJ%2BSkC">http://blip.tv/play/AYGJ%2BSkC</BLIP></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://blip.tv/play/AYGJ+SkC',
				'<rt><BLIP id="AYGJ+SkC" url="http://blip.tv/play/AYGJ+SkC">http://blip.tv/play/AYGJ+SkC</BLIP></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('blip');
				}
			],
			[
				'http://www.break.com/video/video-game-playing-frog-wants-more-2278131',
				'<rt><BREAK id="2278131" url="http://www.break.com/video/video-game-playing-frog-wants-more-2278131">http://www.break.com/video/video-game-playing-frog-wants-more-2278131</BREAK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('break');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<rt><CBSNEWS id="50156501" url="http://www.cbsnews.com/video/watch/?id=50156501n">http://www.cbsnews.com/video/watch/?id=50156501n</CBSNEWS></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<rt><COLLEGEHUMOR id="1181601" url="http://www.collegehumor.com/video/1181601/more-than-friends">http://www.collegehumor.com/video/1181601/more-than-friends</COLLEGEHUMOR></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<rt><DAILYMOTION id="x222z1" url="http://www.dailymotion.com/video/x222z1">http://www.dailymotion.com/video/x222z1</DAILYMOTION></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/user/Dailymotion/2#video=x222z1',
				'<rt><DAILYMOTION id="x222z1" url="http://www.dailymotion.com/user/Dailymotion/2#video=x222z1">http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</DAILYMOTION></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://espn.go.com/video/clip?id=espn:9895232',
				'<rt><ESPN id="espn:9895232" url="http://espn.go.com/video/clip?id=espn:9895232">http://espn.go.com/video/clip?id=espn:9895232</ESPN></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<rt><FACEBOOK id="10100658170103643" url="https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater">https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater</FACEBOOK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'https://www.facebook.com/video/video.php?v=10150451523596807',
				'<rt><FACEBOOK id="10150451523596807" url="https://www.facebook.com/video/video.php?v=10150451523596807">https://www.facebook.com/video/video.php?v=10150451523596807</FACEBOOK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david',
				'<rt><FUNNYORDIE id="bf313bd8b4" url="http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david">http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david</FUNNYORDIE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('funnyordie');
				}
			],
			[
				'http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/',
				'<rt><GAMESPOT id="6415176" url="http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/">http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/</GAMESPOT></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/',
				'<rt><GAMESPOT id="6412922" url="http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/">http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/</GAMESPOT></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/',
				'<rt><GAMESPOT id="6414307" url="http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/">http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/</GAMESPOT></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<rt><GIST id="s9e/6806305" url="https://gist.github.com/s9e/6806305">https://gist.github.com/s9e/6806305</GIST></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/6806305',
				'<rt><GIST id="6806305" url="https://gist.github.com/6806305">https://gist.github.com/6806305</GIST></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599',
				'<rt><GIST id="s9e/6806305/ad88d904b082c8211afa040162402015aacb8599" url="https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599">https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599</GIST></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761',
				'<rt><GROOVESHARK playlistid="74854761" url="http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761">http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761</GROOVESHARK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761',
				'<rt><GROOVESHARK playlistid="74854761" url="http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761">http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761</GROOVESHARK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('grooveshark');
				}
			],
			[
				'http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer',
				'<rt><IGN id="http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer" url="http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer">http://uk.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer</IGN></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ign');
				}
			],
			[
				'http://www.indiegogo.com/projects/513633',
				'<rt><INDIEGOGO id="513633" url="http://www.indiegogo.com/projects/513633">http://www.indiegogo.com/projects/513633</INDIEGOGO></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<rt><KICKSTARTER id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=</KICKSTARTER></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html',
				'<rt><KICKSTARTER card="card" id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html</KICKSTARTER></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<rt><KICKSTARTER id="1869987317/wish-i-was-here-1" url="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html" video="video">http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html</KICKSTARTER></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<rt><LIVELEAK id="3dd_1366238099" url="http://www.liveleak.com/view?i=3dd_1366238099">http://www.liveleak.com/view?i=3dd_1366238099</LIVELEAK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<rt><METACAFE id="10785282" url="http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/">http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</METACAFE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<rt><RUTUBE id="4118278" url="http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd">http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd</RUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<rt><SLIDESHARE id="23431564" url="http://www.slideshare.net/Slideshare/how-23431564">http://www.slideshare.net/Slideshare/how-23431564</SLIDESHARE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<rt><SOUNDCLOUD id="98282116" url="http://api.soundcloud.com/tracks/98282116">[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]</SOUNDCLOUD></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<rt><TED id="talks/eli_pariser_beware_online_filter_bubbles.html" url="http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html">http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html</TED></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<rt><TWITCH channel="minigolf2000" url="http://www.twitch.tv/minigolf2000">http://www.twitch.tv/minigolf2000</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/c/2475925',
				'<rt><TWITCH channel="minigolf2000" chapter_id="2475925" url="http://www.twitch.tv/minigolf2000/c/2475925">http://www.twitch.tv/minigolf2000/c/2475925</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/419320018',
				'<rt><TWITCH archive_id="419320018" channel="minigolf2000" url="http://www.twitch.tv/minigolf2000/b/419320018">http://www.twitch.tv/minigolf2000/b/419320018</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<rt><VIMEO id="67207222" url="http://vimeo.com/67207222">http://vimeo.com/67207222</VIMEO></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'http://vimeo.com/channels/staffpicks/67207222',
				'<rt><VIMEO id="67207222" url="http://vimeo.com/channels/staffpicks/67207222">http://vimeo.com/channels/staffpicks/67207222</VIMEO></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'https://vine.co/v/bYwPIluIipH',
				'<rt><VINE id="bYwPIluIipH" url="https://vine.co/v/bYwPIluIipH">https://vine.co/v/bYwPIluIipH</VINE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vine');
				}
			],
			[
				'http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0',
				'<rt><WSHH id="wshhZ8F22UtJ8sLHdja0" url="http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0">http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0</WSHH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61',
				'<rt><WSHH id="wshh2SXFFe7W14DqQx61" url="http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61">http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61</WSHH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'[media=youtube]-cEzsCAzTak[/media]',
				'<rt><YOUTUBE id="-cEzsCAzTak">[media=youtube]-cEzsCAzTak[/media]</YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<rt><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel">[media]http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel[/media]</YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak"><st>[YOUTUBE]</st>-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel"><st>[YOUTUBE]</st>http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?feature=player_embedded&v=-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak"><st>[YOUTUBE]</st>http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/v/-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/v/-cEzsCAzTak"><st>[YOUTUBE]</st>http://www.youtube.com/v/-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://youtu.be/-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak" url="http://youtu.be/-cEzsCAzTak"><st>[YOUTUBE]</st>http://youtu.be/-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'<rt>Check this: <YOUTUBE id="-cEzsCAzTak" url="http://www.youtube.com/watch?v=-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</YOUTUBE> and that: <URL url="http://example.com">http://example.com</URL></rt>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'http://www.break.com/video/video-game-playing-frog-wants-more-2278131',
				'<iframe width="464" height="290" src="http://www.break.com/embed/2278131" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('break');
				}
			],
			[
				'http://www.cbsnews.com/video/watch/?id=50156501n',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="425" height="279" data="http://cnettv.cnet.com/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="si=254&amp;contentValue=50156501&amp;shareUrl=http://www.cbsnews.com/video/watch/?id=50156501n"><embed type="application/x-shockwave-flash" src="http://cnettv.cnet.com/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf" width="425" height="279" allowfullscreen="" flashvars="si=254&amp;contentValue=50156501&amp;shareUrl=http://www.cbsnews.com/video/watch/?id=50156501n"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('cbsnews');
				}
			],
			[
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<iframe width="600" height="369" src="http://www.collegehumor.com/e/1181601" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/x222z1"><param name="allowfullscreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/x222z1" width="560" height="315" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://espn.go.com/video/clip?id=espn:9895232',
				'<script src="http://player.espn.com/player.js?playerBrandingId=4ef8000cbaf34c1687a7d9a26fe0e89e&amp;adSetCode=91cDU6NuXTGKz3OdjOxFdAgJVtQcKJnI&amp;pcode=1kNG061cgaoolOncv54OAO1ceO-I&amp;width=576&amp;height=324&amp;externalId=espn:9895232&amp;thruParam_espn-ui%5BautoPlay%5D=false&amp;thruParam_espn-ui%5BplayRelatedExternally%5D=true"></script>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('espn');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<iframe width="560" height="315" src="https://www.facebook.com/video/embed?video_id=10100658170103643" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
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
				'<iframe width="640" height="400" src="http://www.gamespot.com/videos/embed/6415176/" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gamespot');
				}
			],
			[
				'https://gist.github.com/s9e/6806305',
				'<script src="https://gist.github.com/s9e/6806305.js"></script>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('gist');
				}
			],
			[
				'http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="250" height="250" data="http://grooveshark.com/widget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID=74854761&amp;songID="><embed type="application/x-shockwave-flash" src="http://grooveshark.com/widget.swf" width="250" height="250" allowfullscreen="" flashvars="playlistID=74854761&amp;songID="></object>',
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
				'<iframe width="224" height="486" src="http://www.indiegogo.com/project/513633/widget" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('indiegogo');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1?ref=',
				'<iframe width="220" height="380" src="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html',
				'<iframe width="480" height="360" src="http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('kickstarter');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<iframe width="560" height="315" src="http://www.liveleak.com/e/3dd_1366238099" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<iframe width="560" height="315" src="http://www.metacafe.com/embed/10785282/" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd',
				'<iframe width="720" height="405" src="http://rutube.ru/video/embed/4118278" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('rutube');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<iframe width="427" height="356" src="http://www.slideshare.net/slideshow/embed_code/23431564" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<iframe width="560" height="166" src="https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F98282116" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<iframe width="560" height="315" src="http://embed.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/live_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=minigolf2000"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/live_embed_player.swf" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/c/2475925',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/archive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=minigolf2000&amp;chapter_id=2475925"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/archive_embed_player.swf" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/419320018',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/archive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel=minigolf2000&amp;archive_id=419320018"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/archive_embed_player.swf" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<iframe width="560" height="315" src="http://player.vimeo.com/video/67207222" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
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
				'http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="448" height="374" data="http://www.worldstarhiphop.com/videos/e/16711680/wshhZ8F22UtJ8sLHdja0"><param name="allowfullscreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.worldstarhiphop.com/videos/e/16711680/wshhZ8F22UtJ8sLHdja0" width="448" height="374" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="448" height="374" data="http://www.worldstarhiphop.com/videos/e/16711680/wshh2SXFFe7W14DqQx61"><param name="allowfullscreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.worldstarhiphop.com/videos/e/16711680/wshh2SXFFe7W14DqQx61" width="448" height="374" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('wshh');
				}
			],
			[
				'[media=youtube]-cEzsCAzTak[/media]',
				'<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE=http://www.youtube.com/watch?v=-cEzsCAzTak]Hi!',
				'<iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>Hi!',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak',
				'Check this: <iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'Check this: <iframe width="560" height="315" src="//www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe> and that: <a href="http://example.com">http://example.com</a>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
					$configurator->MediaEmbed->add('youtube');
				}
			],
		];
	}
}