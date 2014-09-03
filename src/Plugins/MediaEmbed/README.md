## Synopsis

This plugin allows the user to embed content from allowed sites using a `[media]` BBCode, site-specific BBCodes such as `[youtube]`, or from simply posting a supported URL in plain text.

It is designed to be able to parse any of the following forms:

 * `[media]http://www.youtube.com/watch?v=-cEzsCAzTak[/media]` *(simplest form)*
 * `[media=youtube]-cEzsCAzTak[/media]` *(from [XenForo's BB Code Media Sites](http://xenforo.com/help/bb-code-media-sites/))*
 * `[youtube]http://youtu.be/watch?v=-cEzsCAzTak[/youtube]` *(from various forum softwares such as [phpBB](https://www.phpbb.com/customise/db/bbcode/youtube/))*
 * `[youtube=http://www.youtube.com/watch?v=-cEzsCAzTak]` *(from [WordPress's YouTube short code](http://en.support.wordpress.com/videos/youtube/))*
 * `[youtube]-cEzsCAzTak[/youtube]` *(from various forum softwares such as [vBulletin](http://www.vbulletin.com/forum/forum/vbulletin-3-8/vbulletin-3-8-questions-problems-and-troubleshooting/vbulletin-quick-tips-and-customizations/204206-how-to-make-a-youtube-bb-code))*
 * `http://www.youtube.com/watch?v=-cEzsCAzTak` *(plain URLs are turned into embedded content)*

Has built-in support for Dailymotion, Facebook, LiveLeak, Twitch, YouTube [and more](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/MediaEmbed/Configurator/sites.xml).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add('dailymotion');
$configurator->MediaEmbed->add('facebook');
$configurator->MediaEmbed->add('youtube');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$examples = [
	'[media]http://www.dailymotion.com/video/x222z1[/media]',
	'https://www.facebook.com/video/video.php?v=10100658170103643',
	'[youtube]-cEzsCAzTak[/youtube]'
];

foreach ($examples as $text)
{
	$xml  = $parser->parse($text);
	$html = $renderer->render($xml);

	echo $html, "\n";
}
```
```html
<iframe width="560" height="315" src="//www.dailymotion.com/embed/video/x222z1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#10100658170103643" onload="var a=Math.random();window.addEventListener('message',function(b){if(b.data.id==a)style.height=b.data.height+'px'});contentWindow.postMessage('s9e:'+a,src.substr(0,src.indexOf('/',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak?controls=2"></iframe>
```

### Configure a site manually

In addition to the sites that are directly available by name, you can define new, custom sites. More examples are available in the [Cookbook](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/README.md).

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->MediaEmbed->add(
	'youtube',
	[
		'host'    => 'youtube.com',
		'extract' => "!youtube\\.com/watch\\?v=(?'id'[-0-9A-Z_a-z]+)!",
		'iframe'  => [
			'width'  => 560,
			'height' => 315,
			'src'    => 'http://www.youtube.com/embed/{@id}'
		]
	]
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[youtube]http://www.youtube.com/watch?v=-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe>
```

### More examples

You can find more examples [in the Cookbook](https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook#plugins).

### Supported sites

<table>
	<tr>
		<th>Id</th>
		<th>Site</th>
		<th>Example URLs</th>
	</tr>
	<tr>
		<td><code>abcnews</code></td>
		<td>ABC News</td>
		<td>http://abcnews.go.com/WNN/video/dog-goes-wild-when-owner-leaves-22936610</td>
	</tr>
	<tr>
		<td><code>amazon</code></td>
		<td>Amazon Product</td>
		<td>http://www.amazon.ca/gp/product/B00GQT1LNO/<br/>http://www.amazon.co.jp/gp/product/B003AKZ6I8/<br/>http://www.amazon.co.uk/gp/product/B00BET0NR6/<br/>http://www.amazon.com/dp/B002MUC0ZY<br/>http://www.amazon.com/The-BeerBelly-200-001-80-Ounce-Belly/dp/B001RB2CXY/<br/>http://www.amazon.com/gp/product/B0094H8H7I<br/>http://www.amazon.de/Netgear-WN3100RP-100PES-Repeater-integrierte-Steckdose/dp/B00ET2LTE6/<br/>http://www.amazon.fr/Vans-Authentic-Baskets-mixte-adulte/dp/B005NIKPAY/<br/>http://www.amazon.it/gp/product/B00JGOMIP6/</td>
	</tr>
	<tr>
		<td><code>audioboo</code></td>
		<td>Audioboo</td>
		<td>http://audioboo.fm/boos/2439994-deadline-day-update</td>
	</tr>
	<tr>
		<td><code>audiomack</code></td>
		<td>Audiomack</td>
		<td>http://www.audiomack.com/song/your-music-fix/jammin-kungs-remix-1<br/>http://www.audiomack.com/album/chance-the-rapper/acid-rap</td>
	</tr>
	<tr>
		<td><code>bandcamp</code></td>
		<td>Bandcamp</td>
		<td>http://proleter.bandcamp.com/album/curses-from-past-times-ep<br/>http://proleter.bandcamp.com/track/downtown-irony<br/>http://therunons.bandcamp.com/track/still-feel</td>
	</tr>
	<tr>
		<td><code>blip</code></td>
		<td>Blip</td>
		<td>http://blip.tv/blip-on-blip/damian-bruno-and-vinyl-rewind-blip-on-blip-58-5226104<br/>http://blip.tv/play/g6VTgpjxbQA</td>
	</tr>
	<tr>
		<td><code>break</code></td>
		<td>Break</td>
		<td>http://www.break.com/video/video-game-playing-frog-wants-more-2278131</td>
	</tr>
	<tr>
		<td><code>cbsnews</code></td>
		<td>CBS News Video</td>
		<td>http://www.cbsnews.com/video/watch/?id=50156501n<br/>http://www.cbsnews.com/videos/is-the-us-stock-market-rigged</td>
	</tr>
	<tr>
		<td><code>cnbc</code></td>
		<td>CNBC</td>
		<td>http://video.cnbc.com/gallery/?video=3000269279</td>
	</tr>
	<tr>
		<td><code>cnn</code></td>
		<td>CNN</td>
		<td>http://edition.cnn.com/video/data/2.0/video/showbiz/2013/10/25/spc-preview-savages-stephen-king-thor.cnn.html<br/>http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html</td>
	</tr>
	<tr>
		<td><code>cnnmoney</code></td>
		<td>CNNMoney</td>
		<td>http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/</td>
	</tr>
	<tr>
		<td><code>colbertnation</code></td>
		<td>Colbert Nation</td>
		<td>http://thecolbertreport.cc.com/videos/gh6urb/neil-degrasse-tyson-pt--1</td>
	</tr>
	<tr>
		<td><code>collegehumor</code></td>
		<td>CollegeHumor</td>
		<td>http://www.collegehumor.com/video/1181601/more-than-friends</td>
	</tr>
	<tr>
		<td><code>comedycentral</code></td>
		<td>Comedy Central</td>
		<td>http://www.cc.com/video-clips/uu5qz4/key-and-peele-dueling-hats<br/>http://www.comedycentral.com/video-clips/uu5qz4/key-and-peele-dueling-hats<br/>http://tosh.cc.com/video-clips/aet4lh/rc-car-crash</td>
	</tr>
	<tr>
		<td><code>coub</code></td>
		<td>Coub</td>
		<td>http://coub.com/view/6veusoty</td>
	</tr>
	<tr>
		<td><code>dailymotion</code></td>
		<td>Dailymotion</td>
		<td>http://www.dailymotion.com/video/x222z1<br/>http://www.dailymotion.com/user/Dailymotion/2#video=x222z1</td>
	</tr>
	<tr>
		<td><code>dailyshow</code></td>
		<td>The Daily Show with Jon Stewart</td>
		<td>http://www.thedailyshow.com/watch/mon-july-16-2012/louis-c-k-<br/>http://www.thedailyshow.com/collection/429537/shutstorm-2013/429508<br/>http://thedailyshow.cc.com/videos/elvsf4/what-not-to-buy</td>
	</tr>
	<tr>
		<td><code>ebay</code></td>
		<td>eBay</td>
		<td>http://www.ebay.com/itm/Converse-All-Star-Chuck-Taylor-Black-Hi-Canvas-M9160-Men-/251053262701<br/>http://www.ebay.co.uk/itm/Converse-Classic-Chuck-Taylor-Low-Trainer-Sneaker-All-Star-OX-NEW-sizes-Shoes-/230993099153<br/>http://www.ebay.de/itm/Converse-Chucks-All-Star-OX-Klassiker-Gr-35-48-/320748648909<br/>http://www.ebay.fr/itm/CONVERSE-CHUCK-TAYLOR-AS-CORE-OX-All-Star-Sneakers-Men-Women-Free-Shipping-/380728186640<br/>http://www.ebay.at/itm/Tea-Matter-Teesieb-mit-Zwitscherton-Alessi-Rot-Schwarz-Stahl-glnzend-/251585352862<br/>http://www.ebay.com/itm/261416432518</td>
	</tr>
	<tr>
		<td><code>eighttracks</code></td>
		<td>8tracks</td>
		<td>http://8tracks.com/fingerlickengood/just-nujabes<br/>http://8tracks.com/midna/2242699</td>
	</tr>
	<tr>
		<td><code>espn</code></td>
		<td>ESPN</td>
		<td>http://espn.go.com/video/clip?id=11255783<br/>http://m.espn.go.com/general/video?vid=11255783<br/>http://espndeportes.espn.go.com/videohub/video/clipDeportes?id=2134782<br/>http://espn.go.com/video/clip?id=espn:11195358</td>
	</tr>
	<tr>
		<td><code>facebook</code></td>
		<td>Facebook</td>
		<td>https://www.facebook.com/FacebookDevelopers/posts/10151471074398553<br/>https://www.facebook.com/photo.php?v=10100658170103643&amp;set=vb.20531316728&amp;type=3&amp;theater<br/>https://www.facebook.com/video/video.php?v=10150451523596807<br/>https://www.facebook.com/photo.php?fbid=10152476416772631</td>
	</tr>
	<tr>
		<td><code>foxnews</code></td>
		<td>Fox News</td>
		<td>http://video.foxnews.com/v/3592758613001/reddit-helps-fund-homemade-hot-sauce-venture/</td>
	</tr>
	<tr>
		<td><code>funnyordie</code></td>
		<td>Funny or Die</td>
		<td>http://www.funnyordie.com/videos/bf313bd8b4/murdock-with-keith-david</td>
	</tr>
	<tr>
		<td><code>gamespot</code></td>
		<td>Gamespot</td>
		<td>http://www.gamespot.com/destiny/videos/destiny-the-moon-trailer-6415176/<br/>http://www.gamespot.com/events/game-crib-tsm-snapdragon/gamecrib-extras-cooking-with-dan-dinh-6412922/<br/>http://www.gamespot.com/videos/beat-the-pros-pax-prime-2013/2300-6414307/</td>
	</tr>
	<tr>
		<td><code>gametrailers</code></td>
		<td>GameTrailers</td>
		<td>http://www.gametrailers.com/videos/jz8rt1/tom-clancy-s-the-division-vgx-2013--world-premiere-featurette-<br/>http://www.gametrailers.com/reviews/zalxz0/crimson-dragon-review<br/>http://www.gametrailers.com/full-episodes/zdzfok/pop-fiction-episode-40--jak-ii--sandover-village</td>
	</tr>
	<tr>
		<td><code>getty</code></td>
		<td>Getty Images</td>
		<td>http://gty.im/3232182<br/>http://www.gettyimages.com/detail/3232182<br/>http://www.gettyimages.com/detail/news-photo/the-beatles-travel-by-coach-to-the-west-country-for-some-news-photo/3232182<br/>http://www.gettyimages.co.uk/detail/3232182</td>
	</tr>
	<tr>
		<td><code>gfycat</code></td>
		<td>Gfycat</td>
		<td>http://gfycat.com/SereneIllfatedCapybara<br/>http://giant.gfycat.com/SereneIllfatedCapybara.gif</td>
	</tr>
	<tr>
		<td><code>gist</code></td>
		<td>GitHub Gist (via custom iframe)</td>
		<td>https://gist.github.com/s9e/6806305<br/>https://gist.github.com/6806305<br/>https://gist.github.com/s9e/6806305/ad88d904b082c8211afa040162402015aacb8599</td>
	</tr>
	<tr>
		<td><code>gofundme</code></td>
		<td>GoFundMe</td>
		<td>http://www.gofundme.com/2p37ao</td>
	</tr>
	<tr>
		<td><code>googleplus</code></td>
		<td>Google+</td>
		<td>https://plus.google.com/+FeliciaDay/posts/XMABm8rLvRW<br/>https://plus.google.com/106189723444098348646/posts/V8AojCoTzxV</td>
	</tr>
	<tr>
		<td><code>googlesheets</code></td>
		<td>Google Sheets</td>
		<td>https://docs.google.com/spreadsheets/d/1f988o68HDvk335xXllJD16vxLBuRcmm3vg6U9lVaYpA<br/>https://docs.google.com/spreadsheet/ccc?key=0An1aCHqyU7FqdGtBUDc1S1NNSWhqY3NidndIa1JuQWc#gid=70</td>
	</tr>
	<tr>
		<td><code>grooveshark</code></td>
		<td>Grooveshark</td>
		<td>http://grooveshark.com/playlist/Purity+Ring+Shrines/74854761<br/>http://grooveshark.com/#!/playlist/Purity+Ring+Shrines/74854761<br/>http://grooveshark.com/s/Soul+Below/4zGL7i?src=5<br/>http://grooveshark.com/#!/s/Soul+Below/4zGL7i?src=5</td>
	</tr>
	<tr>
		<td><code>hulu</code></td>
		<td>Hulu</td>
		<td>http://www.hulu.com/watch/484180</td>
	</tr>
	<tr>
		<td><code>ign</code></td>
		<td>IGN</td>
		<td>http://www.ign.com/videos/2013/07/12/pokemon-x-version-pokemon-y-version-battle-trailer</td>
	</tr>
	<tr>
		<td><code>imdb</code></td>
		<td>IMDb</td>
		<td>http://www.imdb.com/video/imdb/vi2482677785/<br/>http://www.imdb.com/video/epk/vi387296537/</td>
	</tr>
	<tr>
		<td><code>imgur</code></td>
		<td>imgur albums</td>
		<td>http://imgur.com/a/9UGCL</td>
	</tr>
	<tr>
		<td><code>indiegogo</code></td>
		<td>Indiegogo</td>
		<td>http://www.indiegogo.com/projects/gameheart-redesigned</td>
	</tr>
	<tr>
		<td><code>instagram</code></td>
		<td>Instagram</td>
		<td>http://instagram.com/p/gbGaIXBQbn/</td>
	</tr>
	<tr>
		<td><code>internetarchive</code></td>
		<td>Internet Archive</td>
		<td>https://archive.org/details/BillGate99<br/>https://archive.org/details/DFTS2014-05-30</td>
	</tr>
	<tr>
		<td><code>izlesene</code></td>
		<td>İzlesene</td>
		<td>http://www.izlesene.com/video/lily-allen-url-badman/7600704</td>
	</tr>
	<tr>
		<td><code>kickstarter</code></td>
		<td>Kickstarter</td>
		<td>http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1<br/>http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/card.html<br/>http://www.kickstarter.com/projects/1869987317/wish-i-was-here-1/widget/video.html</td>
	</tr>
	<tr>
		<td><code>liveleak</code></td>
		<td>LiveLeak</td>
		<td>http://www.liveleak.com/view?i=3dd_1366238099</td>
	</tr>
	<tr>
		<td><code>metacafe</code></td>
		<td>Metacafe</td>
		<td>http://www.metacafe.com/watch/10785282/chocolate_treasure_chest_epic_meal_time/</td>
	</tr>
	<tr>
		<td><code>mixcloud</code></td>
		<td>Mixcloud</td>
		<td>http://www.mixcloud.com/OneTakeTapes/timsch-one-take-tapes-2/<br/>http://i.mixcloud.com/CH9VU9</td>
	</tr>
	<tr>
		<td><code>msnbc</code></td>
		<td>MSNBC</td>
		<td>http://www.msnbc.com/ronan-farrow-daily/watch/thats-no-moon--300512323725</td>
	</tr>
	<tr>
		<td><code>nytimes</code></td>
		<td>The New York Times Video</td>
		<td>http://www.nytimes.com/video/technology/personaltech/100000002907606/soylent-taste-test.html<br/>http://www.nytimes.com/video/2012/12/17/business/100000001950744/how-wal-mart-conquered-teotihuacan.html</td>
	</tr>
	<tr>
		<td><code>pastebin</code></td>
		<td>Pastebin</td>
		<td>http://pastebin.com/9jEf44nc</td>
	</tr>
	<tr>
		<td><code>podbean</code></td>
		<td>Podbean</td>
		<td>http://dialhforheroclix.podbean.com/e/dial-h-for-heroclix-episode-46-all-ya-need-is-love/</td>
	</tr>
	<tr>
		<td><code>prezi</code></td>
		<td>Prezi</td>
		<td>http://prezi.com/5ye8po_hmikp/10-most-common-rookie-presentation-mistakes/</td>
	</tr>
	<tr>
		<td><code>rdio</code></td>
		<td>Rdio</td>
		<td>http://rd.io/x/QcD7oTdeWevg/<br/>https://www.rdio.com/artist/Hannibal_Buress/album/Animal_Furnace/track/Hands-Free/</td>
	</tr>
	<tr>
		<td><code>rutube</code></td>
		<td>Rutube</td>
		<td>http://rutube.ru/video/b920dc58f1397f1761a226baae4d2f3b/<br/>http://rutube.ru/tracks/4118278.html?v=8b490a46447720d4ad74616f5de2affd</td>
	</tr>
	<tr>
		<td><code>scribd</code></td>
		<td>Scribd</td>
		<td>http://www.scribd.com/doc/237147661/Calculus-2-Test-1-Review?in_collection=5291376</td>
	</tr>
	<tr>
		<td><code>slideshare</code></td>
		<td>SlideShare</td>
		<td>http://www.slideshare.net/Slideshare/how-23431564</td>
	</tr>
	<tr>
		<td><code>soundcloud</code></td>
		<td>SoundCloud</td>
		<td>http://api.soundcloud.com/tracks/98282116<br/>https://soundcloud.com/andrewbird/three-white-horses<br/>https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/</td>
	</tr>
	<tr>
		<td><code>spotify</code></td>
		<td>Spotify</td>
		<td>spotify:track:5JunxkcjfCYcY7xJ29tLai<br/>spotify:trackset:PREFEREDTITLE:5Z7ygHQo02SUrFmcgpwsKW,1x6ACsKV4UdWS2FMuPFUiT,4bi73jCM02fMpkI11Lqmfe<br/>http://open.spotify.com/user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt<br/>https://play.spotify.com/album/5OSzFvFAYuRh93WDNCTLEz</td>
	</tr>
	<tr>
		<td><code>strawpoll</code></td>
		<td>Straw Poll</td>
		<td>http://strawpoll.me/738091</td>
	</tr>
	<tr>
		<td><code>teamcoco</code></td>
		<td>Team Coco</td>
		<td>http://teamcoco.com/video/serious-jibber-jabber-a-scott-berg-full-episode<br/>http://teamcoco.com/video/73784/historian-a-scott-berg-serious-jibber-jabber-with-conan-obrien</td>
	</tr>
	<tr>
		<td><code>ted</code></td>
		<td>TED Talks</td>
		<td>http://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles.html<br/>http://embed.ted.com/playlists/26/our_digital_lives.html</td>
	</tr>
	<tr>
		<td><code>theatlantic</code></td>
		<td>The Atlantic Video</td>
		<td>http://www.theatlantic.com/video/index/358928/computer-vision-syndrome-and-you/</td>
	</tr>
	<tr>
		<td><code>theonion</code></td>
		<td>The Onion</td>
		<td>http://www.theonion.com/video/nation-successfully-completes-mothers-day-by-918-a,35998/</td>
	</tr>
	<tr>
		<td><code>tinypic</code></td>
		<td>TinyPic videos</td>
		<td>http://tinypic.com/player.php?v=29x86j9&amp;s=8</td>
	</tr>
	<tr>
		<td><code>traileraddict</code></td>
		<td>Trailer Addict</td>
		<td>http://www.traileraddict.com/the-amazing-spider-man-2/super-bowl-tv-spot</td>
	</tr>
	<tr>
		<td><code>twitch</code></td>
		<td>Twitch</td>
		<td>http://www.twitch.tv/minigolf2000<br/>http://www.twitch.tv/amazhs/c/4493103<br/>http://www.twitch.tv/minigolf2000/b/497929990<br/>http://www.twitch.tv/m/57217</td>
	</tr>
	<tr>
		<td><code>twitter</code></td>
		<td>Twitter (via custom iframe)</td>
		<td>https://twitter.com/BarackObama/statuses/266031293945503744<br/>https://twitter.com/DerekTVShow/status/463372588690202624<br/>https://twitter.com/#!/BarackObama/status/266031293945503744</td>
	</tr>
	<tr>
		<td><code>ustream</code></td>
		<td>Ustream</td>
		<td>http://www.ustream.tv/channel/ps4-ustream-gameplay<br/>http://www.ustream.tv/baja1000tv<br/>http://www.ustream.tv/recorded/40688256</td>
	</tr>
	<tr>
		<td><code>vevo</code></td>
		<td>VEVO</td>
		<td>http://www.vevo.com/watch/USUV71400682<br/>http://www.vevo.com/watch/eminem/the-monster-explicit/USUV71302925</td>
	</tr>
	<tr>
		<td><code>viagame</code></td>
		<td>Viagame</td>
		<td>http://www.viagame.com/channels/hearthstone-championship/405177</td>
	</tr>
	<tr>
		<td><code>vimeo</code></td>
		<td>Vimeo</td>
		<td>http://vimeo.com/67207222<br/>http://vimeo.com/channels/staffpicks/67207222</td>
	</tr>
	<tr>
		<td><code>vine</code></td>
		<td>Vine</td>
		<td>https://vine.co/v/bYwPIluIipH</td>
	</tr>
	<tr>
		<td><code>vocaroo</code></td>
		<td>Vocaroo</td>
		<td>http://vocaroo.com/i/s0dRy3rZ47bf</td>
	</tr>
	<tr>
		<td><code>vk</code></td>
		<td>VK</td>
		<td>http://vkontakte.ru/video-7016284_163645555<br/>http://vk.com/video226156999_168963041<br/>http://vk.com/newmusicvideos?z=video-13895667_161988074<br/>http://vk.com/video_ext.php?oid=121599878&amp;id=165723901&amp;hash=e06b0878046e1d32</td>
	</tr>
	<tr>
		<td><code>vube</code></td>
		<td>Vube</td>
		<td>http://vube.com/s/v7JeV6<br/>http://vube.com/William+Wei/best-drummer-ever-hd/Y8NUZ69Tf7?t=s</td>
	</tr>
	<tr>
		<td><code>wshh</code></td>
		<td>WorldStarHipHop</td>
		<td>http://www.worldstarhiphop.com/videos/video.php?v=wshhZ8F22UtJ8sLHdja0<br/>http://m.worldstarhiphop.com/video.php?v=wshh2SXFFe7W14DqQx61<br/>http://www.worldstarhiphop.com/featured/71630</td>
	</tr>
	<tr>
		<td><code>wsj</code></td>
		<td>The Wall Street Journal Online</td>
		<td>http://live.wsj.com/#!09FB2B3B-583E-4284-99D8-FEF6C23BE4E2<br/>http://live.wsj.com/video/seahawks-qb-russell-wilson-on-super-bowl-win/9B3DF790-9D20-442C-B564-51524B06FD26.html<br/>http://live.wsj.com/video/seth-rogen-emotional-appeal-over-alzheimer/3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8.html?mod=trending_now_video_4#!3885A1E1-D5DE-443A-AA45-6A8F6BB8FBD8</td>
	</tr>
	<tr>
		<td><code>yahooscreen</code></td>
		<td>Yahoo! Screen</td>
		<td>https://screen.yahoo.com/mr-short-term-memory-000000263.html<br/>https://screen.yahoo.com/dana-carvey-snl-skits/church-chat-satan-000000502.html</td>
	</tr>
	<tr>
		<td><code>youku</code></td>
		<td>Youku</td>
		<td>http://v.youku.com/v_show/id_XNzQwNjcxNDM2.html</td>
	</tr>
	<tr>
		<td><code>youtube</code></td>
		<td>YouTube</td>
		<td>http://www.youtube.com/watch?v=-cEzsCAzTak<br/>http://youtu.be/-cEzsCAzTak<br/>http://www.youtube.com/watch?feature=player_detailpage&amp;v=jofNR_WkoCE#t=40<br/>http://www.youtube.com/watch?v=pC35x6iIPmo&amp;list=PLOU2XLYxmsIIxJrlMIY5vYXAFcO5g83gA</td>
	</tr>
</table>