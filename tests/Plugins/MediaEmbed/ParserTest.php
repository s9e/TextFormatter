<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed;

use s9e\TextFormatter\Configurator;
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
				'http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites',
				'<rt><SLIDESHARE id="21112125">http://www.slideshare.net/Slideshare/10-million-uploads-our-favorites</SLIDESHARE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
		];
	}

	public function getParsingTests()
	{
		return [
			// Scraping tests that don't require network
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
				'[media]http://example.org/123?x"> foo="bar[/media]',
				'<pt>[media]http://example.org/123?x"&gt; foo="bar[/media]</pt>',
				['captureURLs' => false],
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
				// Ensure that we don't scrape the URL if it doesn't match
				'[media]http://example.org/123[/media]',
				'<pt>[media]http://example.org/123[/media]</pt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'   => 'example.org',
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
				'<rt><EXAMPLE id="12">http://example.invalid/123</EXAMPLE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add(
						'example',
						[
							'host'    => 'example.invalid',
							'extract' => "#/(?'id'[0-9]{2})#",
							'scrape'  => [
								'match'   => '.',
								'extract' => "/(?'id'[0-9]+)/"
							],
							'iframe'  => ['width' => 1, 'height' => 1, 'src' => '{@id}']
						]
					);
				}
			],
			// Bundled sites
			[
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<rt><COLLEGEHUMOR id="1181601">http://www.collegehumor.com/video/1181601/more-than-friends</COLLEGEHUMOR></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<rt><DAILYMOTION id="x222z1">http://www.dailymotion.com/video/x222z1</DAILYMOTION></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'http://www.dailymotion.com/user/Dailymotion/2#video=x222z1',
				'<rt><DAILYMOTION id="x222z1">http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</DAILYMOTION></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<rt><FACEBOOK id="10100658170103643">https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater</FACEBOOK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<rt><LIVELEAK id="3dd_1366238099">http://www.liveleak.com/view?i=3dd_1366238099</LIVELEAK></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<rt><METACAFE id="10785282">http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</METACAFE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<rt><SLIDESHARE id="23431564">http://www.slideshare.net/Slideshare/how-23431564</SLIDESHARE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<rt><SOUNDCLOUD id="98282116">[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]</SOUNDCLOUD></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<rt><TED path="talks/eli_pariser_beware_online_filter_bubbles.html">http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html</TED></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				// Some people might just copy/paste the embed code
				'[media]<iframe src="http://embed.ted.com/playlists/26/our_digital_lives.html" width="640" height="360" frameborder="0" scrolling="no" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>[/media]',
				'<rt><TED path="playlists/26/our_digital_lives.html">[media]&lt;iframe src="http://embed.ted.com/playlists/26/our_digital_lives.html" width="640" height="360" frameborder="0" scrolling="no" webkitAllowFullScreen mozallowfullscreen allowFullScreen&gt;&lt;/iframe&gt;[/media]</TED></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<rt><TWITCH channel="minigolf2000">http://www.twitch.tv/minigolf2000</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/c/2475925',
				'<rt><TWITCH channel="minigolf2000" chapter_id="2475925">http://www.twitch.tv/minigolf2000/c/2475925</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/419320018',
				'<rt><TWITCH channel="minigolf2000" archive_id="419320018">http://www.twitch.tv/minigolf2000/b/419320018</TWITCH></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<rt><VIMEO id="67207222">http://vimeo.com/67207222</VIMEO></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'http://vimeo.com/channels/staffpicks/67207222',
				'<rt><VIMEO id="67207222">http://vimeo.com/channels/staffpicks/67207222</VIMEO></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
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
				'<rt><YOUTUBE id="-cEzsCAzTak">[media]http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel[/media]</YOUTUBE></rt>',
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
				'<rt><YOUTUBE id="-cEzsCAzTak"><st>[YOUTUBE]</st>http://www.youtube.com/watch?v=-cEzsCAzTak&amp;feature=channel<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?feature=player_embedded&v=-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak"><st>[YOUTUBE]</st>http://www.youtube.com/watch?feature=player_embedded&amp;v=-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/v/-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak"><st>[YOUTUBE]</st>http://www.youtube.com/v/-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://youtu.be/-cEzsCAzTak[/YOUTUBE]',
				'<rt><YOUTUBE id="-cEzsCAzTak"><st>[YOUTUBE]</st>http://youtu.be/-cEzsCAzTak<et>[/YOUTUBE]</et></YOUTUBE></rt>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'<rt>Check this: <YOUTUBE id="-cEzsCAzTak">http://www.youtube.com/watch?v=-cEzsCAzTak</YOUTUBE> and that: <URL url="http://example.com">http://example.com</URL></rt>',
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
				'http://www.collegehumor.com/video/1181601/more-than-friends',
				'<iframe width="600" height="369" src="http://www.collegehumor.com/e/1181601" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('collegehumor');
				}
			],
			[
				'http://www.dailymotion.com/video/x222z1',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/x222z1"><param name="allowFullScreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/x222z1" width="560" height="315" allowfullscreen=""></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('dailymotion');
				}
			],
			[
				'https://www.facebook.com/photo.php?v=10100658170103643&set=vb.20531316728&type=3&theater',
				'<iframe width="560" height="315" src="https://www.facebook.com/video/embed?video_id=10100658170103643" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('facebook');
				}
			],
			[
				'http://www.liveleak.com/view?i=3dd_1366238099',
				'<iframe width="560" height="315" src="http://www.liveleak.com/e/3dd_1366238099" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('liveleak');
				}
			],
			[
				'http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/',
				'<iframe width="560" height="315" src="http://www.metacafe.com/embed/10785282/" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('metacafe');
				}
			],
			[
				'http://www.slideshare.net/Slideshare/how-23431564',
				'<iframe width="427" height="356" src="http://www.slideshare.net/slideshow/embed_code/23431564" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('slideshare');
				}
			],
			[
				// Taken from the "WordPress Code" button of the page
				'[soundcloud url="http://api.soundcloud.com/tracks/98282116" params="" width=" 100%" height="166" iframe="true" /]',
				'<iframe width="560" height="166" src="https://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F98282116" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('soundcloud');
				}
			],
			[
				'http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html',
				'<iframe width="560" height="315" src="http://embed.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('ted');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/live_embed_player.swf"><param name="flashvars" value="channel=minigolf2000"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/live_embed_player.swf"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/c/2475925',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/archive_embed_player.swf"><param name="flashvars" value="channel=minigolf2000&amp;chapter_id=2475925"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/archive_embed_player.swf"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://www.twitch.tv/minigolf2000/b/419320018',
				'<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/archive_embed_player.swf"><param name="flashvars" value="channel=minigolf2000&amp;archive_id=419320018"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/archive_embed_player.swf"></object>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('twitch');
				}
			],
			[
				'http://vimeo.com/67207222',
				'<iframe width="560" height="315" src="http://player.vimeo.com/video/67207222" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('vimeo');
				}
			],
			[
				'[media=youtube]-cEzsCAzTak[/media]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[media]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/media]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]-cEzsCAzTak[/YOUTUBE]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'[YOUTUBE=http://www.youtube.com/watch?v=-cEzsCAzTak]Hi!',
				'<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>Hi!',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak',
				'Check this: <iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe>',
				[],
				function ($configurator)
				{
					$configurator->MediaEmbed->add('youtube');
				}
			],
			[
				'Check this: http://www.youtube.com/watch?v=-cEzsCAzTak and that: http://example.com',
				'Check this: <iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen=""></iframe> and that: <a href="http://example.com">http://example.com</a>',
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

namespace s9e\TextFormatter\Plugins\MediaEmbed;

// Terrible hack ahead: this function will transparently cache the result of file_get_contents
// when used on HTTP URLs in the MediaEmbed namespace
function file_get_contents($filepath)
{
	if (!preg_match('#^(?:compress\\.zlib://)?(http://.*)#', $filepath, $m))
	{
		return call_user_func_array('file_get_contents', func_get_args());
	}

	$url       = $m[1];
	$cacheDir  = __DIR__ . '/../../.cache';
	$cacheFile = $cacheDir . '/http.' . crc32($url);

	$context = stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']]);
	$url     = 'compress.zlib://' . $url;

	if (file_exists($cacheDir))
	{
		if (!file_exists($cacheFile))
		{
			copy($url, $cacheFile, $context);
		}

		return \file_get_contents($cacheFile);
	}

	return \file_get_contents($url, null, $context);
}