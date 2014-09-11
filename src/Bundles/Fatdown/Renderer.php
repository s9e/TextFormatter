<?php
/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\Fatdown;

class Renderer extends \s9e\TextFormatter\Renderer
{
	protected $htmlOutput=true;
	protected $params=[];
	protected static $tagBranches=['BANDCAMP'=>0,'C'=>1,'html:code'=>1,'CODE'=>2,'DAILYMOTION'=>3,'DEL'=>4,'html:del'=>4,'EM'=>5,'EMAIL'=>6,'ESC'=>7,'FACEBOOK'=>8,'FP'=>9,'HE'=>9,'GROOVESHARK'=>10,'H1'=>11,'H2'=>12,'H3'=>13,'H4'=>14,'H5'=>15,'H6'=>16,'HC'=>17,'HR'=>18,'IMG'=>19,'LI'=>20,'html:li'=>20,'LIST'=>21,'LIVELEAK'=>22,'QUOTE'=>23,'SOUNDCLOUD'=>24,'SPOTIFY'=>25,'STRONG'=>26,'html:strong'=>26,'SUP'=>27,'html:sup'=>27,'TWITCH'=>28,'URL'=>29,'VIMEO'=>30,'VINE'=>31,'YOUTUBE'=>32,'br'=>33,'e'=>34,'i'=>34,'s'=>34,'html:abbr'=>35,'html:b'=>36,'html:br'=>37,'html:div'=>38,'html:i'=>39,'html:img'=>40,'html:ins'=>41,'html:ol'=>42,'html:pre'=>43,'html:rb'=>44,'html:rp'=>45,'html:rt'=>46,'html:rtc'=>47,'html:ruby'=>48,'html:span'=>49,'html:sub'=>50,'html:table'=>51,'html:tbody'=>52,'html:td'=>53,'html:tfoot'=>54,'html:th'=>55,'html:thead'=>56,'html:tr'=>57,'html:u'=>58,'html:ul'=>59,'p'=>60];
	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props['out'], $props['proc'], $props['source']);
		return array_keys($props);
	}
	public function renderRichText($xml)
	{
		if (!isset(self::$quickRenderingTest) || !preg_match(self::$quickRenderingTest, $xml))
			try
			{
				return $this->renderQuick($xml);
			}
			catch (\Exception $e)
			{
			}
		$dom = $this->loadXML($xml);
		$this->out = '';
		$this->at($dom->documentElement);
		return $this->out;
	}
	protected function at(\DOMNode $root)
	{
		if ($root->nodeType === 3)
			$this->out .= htmlspecialchars($root->textContent,0);
		else
			foreach ($root->childNodes as $node)
				if (!isset(self::$tagBranches[$node->nodeName]))
					$this->at($node);
				else
				{
					$tb = self::$tagBranches[$node->nodeName];
					if($tb<31)if($tb<16)if($tb<8)if($tb<4)if($tb===0){$this->out.='<iframe width="400" height="400" allowfullscreen="" frameborder="0" scrolling="no" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if($node->hasAttribute('album_id')){$this->out.='album='.htmlspecialchars($node->getAttribute('album_id'),2);if($node->hasAttribute('track_num'))$this->out.='/t='.htmlspecialchars($node->getAttribute('track_num'),2);}else$this->out.='track='.htmlspecialchars($node->getAttribute('track_id'),2);$this->out.='"></iframe>';}elseif($tb===1){$this->out.='<code>';$this->at($node);$this->out.='</code>';}elseif($tb===2){$this->out.='<pre><code class="'.htmlspecialchars($node->getAttribute('lang'),2).'">';$this->at($node);$this->out.='</code></pre>';}else$this->out.='<iframe width="560" height="315" src="//www.dailymotion.com/embed/video/'.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen="" frameborder="0" scrolling="no"></iframe>';elseif($tb===4){$this->out.='<del>';$this->at($node);$this->out.='</del>';}elseif($tb===5){$this->out.='<em>';$this->at($node);$this->out.='</em>';}elseif($tb===6){$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}else$this->at($node);elseif($tb<12)if($tb===8)$this->out.='<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#'.htmlspecialchars($node->getAttribute('id'),2).'" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>';elseif($tb===9)$this->out.=htmlspecialchars($node->getAttribute('char'),0);elseif($tb===10)$this->out.='<object type="application/x-shockwave-flash" typemustmatch="" width="400" height="'.htmlspecialchars(($node->hasAttribute('songid')?40:400),2).'" data="//grooveshark.com/'.htmlspecialchars(($node->hasAttribute('songid')?'songW':'w'),2).'idget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID='.htmlspecialchars($node->getAttribute('playlistid'),2).'&amp;songID='.htmlspecialchars($node->getAttribute('songid'),2).'"><embed type="application/x-shockwave-flash" src="//grooveshark.com/'.htmlspecialchars(($node->hasAttribute('songid')?'songW':'w'),2).'idget.swf" width="400" height="'.htmlspecialchars(($node->hasAttribute('songid')?40:400),2).'" allowfullscreen="" flashvars="playlistID='.htmlspecialchars($node->getAttribute('playlistid'),2).'&amp;songID='.htmlspecialchars($node->getAttribute('songid'),2).'"></object>';else{$this->out.='<h1>';$this->at($node);$this->out.='</h1>';}elseif($tb===12){$this->out.='<h2>';$this->at($node);$this->out.='</h2>';}elseif($tb===13){$this->out.='<h3>';$this->at($node);$this->out.='</h3>';}elseif($tb===14){$this->out.='<h4>';$this->at($node);$this->out.='</h4>';}else{$this->out.='<h5>';$this->at($node);$this->out.='</h5>';}elseif($tb<24)if($tb<20){if($tb===16){$this->out.='<h6>';$this->at($node);$this->out.='</h6>';}elseif($tb===17)$this->out.='<!--'.htmlspecialchars($node->getAttribute('content'),0).'-->';elseif($tb===18)$this->out.='<hr>';else{$this->out.='<img src="'.htmlspecialchars($node->getAttribute('src'),2).'"';if($node->hasAttribute('alt'))$this->out.=' alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';}}elseif($tb===20){$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($tb===21)if(!$node->hasAttribute('type')){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}else{$this->out.='<ol>';$this->at($node);$this->out.='</ol>';}elseif($tb===22)$this->out.='<iframe width="640" height="360" src="http://www.liveleak.com/ll_embed?i='.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen="" frameborder="0" scrolling="no"></iframe>';else{$this->out.='<blockquote>';$this->at($node);$this->out.='</blockquote>';}elseif($tb<28)if($tb===24){$this->out.='<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=';if($node->hasAttribute('secret_token')&&$node->hasAttribute('playlist_id'))$this->out.='https://api.soundcloud.com/playlists/'.htmlspecialchars($node->getAttribute('playlist_id'),2).'&amp;secret_token='.htmlspecialchars($node->getAttribute('secret_token'),2);elseif($node->hasAttribute('secret_token')&&$node->hasAttribute('track_id'))$this->out.='https://api.soundcloud.com/tracks/'.htmlspecialchars($node->getAttribute('track_id'),2).'&amp;secret_token='.htmlspecialchars($node->getAttribute('secret_token'),2);else{if((strpos($node->getAttribute('id'),'://')===false))$this->out.='https://soundcloud.com/';$this->out.=htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('secret_token'))$this->out.='&amp;secret_token='.htmlspecialchars($node->getAttribute('secret_token'),2);}$this->out.='"></iframe>';}elseif($tb===25){$this->out.='<iframe width="400" height="480" allowfullscreen="" frameborder="0" scrolling="no" src="https://embed.spotify.com/?view=coverart&amp;uri=';if($node->hasAttribute('uri'))$this->out.=htmlspecialchars($node->getAttribute('uri'),2);else$this->out.='spotify:'.htmlspecialchars(strtr($node->getAttribute('path'),'/',':'),2);$this->out.='"></iframe>';}elseif($tb===26){$this->out.='<strong>';$this->at($node);$this->out.='</strong>';}else{$this->out.='<sup>';$this->at($node);$this->out.='</sup>';}elseif($tb===28){$this->out.='<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="//www.twitch.tv/widgets/';if($node->hasAttribute('archive_id')||$node->hasAttribute('chapter_id'))$this->out.='arch';else$this->out.='l';$this->out.='ive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel='.htmlspecialchars($node->getAttribute('channel'),2);if($node->hasAttribute('archive_id'))$this->out.='&amp;archive_id='.htmlspecialchars($node->getAttribute('archive_id'),2);if($node->hasAttribute('chapter_id'))$this->out.='&amp;chapter_id='.htmlspecialchars($node->getAttribute('chapter_id'),2);$this->out.='&amp;auto_play=false"><embed type="application/x-shockwave-flash" width="620" height="378" allowfullscreen="" src="//www.twitch.tv/widgets/';if($node->hasAttribute('archive_id')||$node->hasAttribute('chapter_id'))$this->out.='arch';else$this->out.='l';$this->out.='ive_embed_player.swf" flashvars="channel='.htmlspecialchars($node->getAttribute('channel'),2);if($node->hasAttribute('archive_id'))$this->out.='&amp;archive_id='.htmlspecialchars($node->getAttribute('archive_id'),2);if($node->hasAttribute('chapter_id'))$this->out.='&amp;chapter_id='.htmlspecialchars($node->getAttribute('chapter_id'),2);$this->out.='&amp;auto_play=false"></object>';}elseif($tb===29){$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';$this->at($node);$this->out.='</a>';}else$this->out.='<iframe width="560" height="315" src="//player.vimeo.com/video/'.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen="" frameborder="0" scrolling="no"></iframe>';elseif($tb<46)if($tb<39){if($tb<35)if($tb===31)$this->out.='<iframe width="480" height="480" src="https://vine.co/v/'.htmlspecialchars($node->getAttribute('id'),2).'/embed/simple" allowfullscreen="" frameborder="0" scrolling="no"></iframe><script async="" src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>';elseif($tb===32){$this->out.='<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/'.htmlspecialchars($node->getAttribute('id'),2).'?controls=2';if($node->hasAttribute('list'))$this->out.='&amp;list='.htmlspecialchars($node->getAttribute('list'),2);if($node->hasAttribute('t')||$node->hasAttribute('m')){$this->out.='&amp;start=';if($node->hasAttribute('t'))$this->out.=htmlspecialchars($node->getAttribute('t'),2);elseif($node->hasAttribute('h'))$this->out.=htmlspecialchars($node->getAttribute('h')*3600+$node->getAttribute('m')*60+$node->getAttribute('s'),2);else$this->out.=htmlspecialchars($node->getAttribute('m')*60+$node->getAttribute('s'),2);}$this->out.='"></iframe>';}elseif($tb===33)$this->out.='<br>';else;elseif($tb===35){$this->out.='<abbr';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';$this->at($node);$this->out.='</abbr>';}elseif($tb===36){$this->out.='<b>';$this->at($node);$this->out.='</b>';}elseif($tb===37)$this->out.='<br>';else{$this->out.='<div';if($node->hasAttribute('class'))$this->out.=' class="'.htmlspecialchars($node->getAttribute('class'),2).'"';$this->out.='>';$this->at($node);$this->out.='</div>';}}elseif($tb<43)if($tb===39){$this->out.='<i>';$this->at($node);$this->out.='</i>';}elseif($tb===40){$this->out.='<img';if($node->hasAttribute('alt'))$this->out.=' alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"';if($node->hasAttribute('height'))$this->out.=' height="'.htmlspecialchars($node->getAttribute('height'),2).'"';if($node->hasAttribute('src'))$this->out.=' src="'.htmlspecialchars($node->getAttribute('src'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';if($node->hasAttribute('width'))$this->out.=' width="'.htmlspecialchars($node->getAttribute('width'),2).'"';$this->out.='>';}elseif($tb===41){$this->out.='<ins>';$this->at($node);$this->out.='</ins>';}else{$this->out.='<ol>';$this->at($node);$this->out.='</ol>';}elseif($tb===43){$this->out.='<pre>';$this->at($node);$this->out.='</pre>';}elseif($tb===44){$this->out.='<rb>';$this->at($node);$this->out.='</rb>';}else{$this->out.='<rp>';$this->at($node);$this->out.='</rp>';}elseif($tb<54){if($tb<50){if($tb===46){$this->out.='<rt>';$this->at($node);$this->out.='</rt>';}elseif($tb===47){$this->out.='<rtc>';$this->at($node);$this->out.='</rtc>';}elseif($tb===48){$this->out.='<ruby>';$this->at($node);$this->out.='</ruby>';}else{$this->out.='<span';if($node->hasAttribute('class'))$this->out.=' class="'.htmlspecialchars($node->getAttribute('class'),2).'"';$this->out.='>';$this->at($node);$this->out.='</span>';}}elseif($tb===50){$this->out.='<sub>';$this->at($node);$this->out.='</sub>';}elseif($tb===51){$this->out.='<table>';$this->at($node);$this->out.='</table>';}elseif($tb===52){$this->out.='<tbody>';$this->at($node);$this->out.='</tbody>';}else{$this->out.='<td';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';$this->out.='>';$this->at($node);$this->out.='</td>';}}elseif($tb<58)if($tb===54){$this->out.='<tfoot>';$this->at($node);$this->out.='</tfoot>';}elseif($tb===55){$this->out.='<th';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';if($node->hasAttribute('scope'))$this->out.=' scope="'.htmlspecialchars($node->getAttribute('scope'),2).'"';$this->out.='>';$this->at($node);$this->out.='</th>';}elseif($tb===56){$this->out.='<thead>';$this->at($node);$this->out.='</thead>';}else{$this->out.='<tr>';$this->at($node);$this->out.='</tr>';}elseif($tb===58){$this->out.='<u>';$this->at($node);$this->out.='</u>';}elseif($tb===59){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}else{$this->out.='<p>';$this->at($node);$this->out.='</p>';}
				}
	}
	private static $static=['/C'=>'</code>','/CODE'=>'</code></pre>','/DEL'=>'</del>','/EM'=>'</em>','/EMAIL'=>'</a>','/ESC'=>'','/H1'=>'</h1>','/H2'=>'</h2>','/H3'=>'</h3>','/H4'=>'</h4>','/H5'=>'</h5>','/H6'=>'</h6>','/LI'=>'</li>','/QUOTE'=>'</blockquote>','/STRONG'=>'</strong>','/SUP'=>'</sup>','/URL'=>'</a>','/html:abbr'=>'</abbr>','/html:b'=>'</b>','/html:code'=>'</code>','/html:del'=>'</del>','/html:div'=>'</div>','/html:i'=>'</i>','/html:ins'=>'</ins>','/html:li'=>'</li>','/html:ol'=>'</ol>','/html:pre'=>'</pre>','/html:rb'=>'</rb>','/html:rp'=>'</rp>','/html:rt'=>'</rt>','/html:rtc'=>'</rtc>','/html:ruby'=>'</ruby>','/html:span'=>'</span>','/html:strong'=>'</strong>','/html:sub'=>'</sub>','/html:sup'=>'</sup>','/html:table'=>'</table>','/html:tbody'=>'</tbody>','/html:td'=>'</td>','/html:tfoot'=>'</tfoot>','/html:th'=>'</th>','/html:thead'=>'</thead>','/html:tr'=>'</tr>','/html:u'=>'</u>','/html:ul'=>'</ul>','C'=>'<code>','DEL'=>'<del>','EM'=>'<em>','ESC'=>'','H1'=>'<h1>','H2'=>'<h2>','H3'=>'<h3>','H4'=>'<h4>','H5'=>'<h5>','H6'=>'<h6>','HR'=>'<hr>','LI'=>'<li>','QUOTE'=>'<blockquote>','STRONG'=>'<strong>','SUP'=>'<sup>','html:b'=>'<b>','html:br'=>'<br>','html:code'=>'<code>','html:del'=>'<del>','html:i'=>'<i>','html:ins'=>'<ins>','html:li'=>'<li>','html:ol'=>'<ol>','html:pre'=>'<pre>','html:rb'=>'<rb>','html:rp'=>'<rp>','html:rt'=>'<rt>','html:rtc'=>'<rtc>','html:ruby'=>'<ruby>','html:strong'=>'<strong>','html:sub'=>'<sub>','html:sup'=>'<sup>','html:table'=>'<table>','html:tbody'=>'<tbody>','html:tfoot'=>'<tfoot>','html:thead'=>'<thead>','html:tr'=>'<tr>','html:u'=>'<u>','html:ul'=>'<ul>'];
	private static $dynamic=['CODE'=>['(^[^ ]+(?> (?!lang=)[^=]+="[^"]*")*(?> lang="([^"]*)")?.*)s','<pre><code class="$1">'],'DAILYMOTION'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe width="560" height="315" src="//www.dailymotion.com/embed/video/$1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>'],'EMAIL'=>['(^[^ ]+(?> (?!email=)[^=]+="[^"]*")*(?> email="([^"]*)")?.*)s','<a href="mailto:$1">'],'FACEBOOK'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe width="560" height="315" src="//s9e.github.io/iframe/facebook.min.html#$1" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,src.substr(0,src.indexOf(\'/\',8)))" allowfullscreen="" frameborder="0" scrolling="no"></iframe>'],'IMG'=>['(^[^ ]+(?> (?!(?>alt|src|title)=)[^=]+="[^"]*")*( alt="[^"]*")?(?> (?!(?>src|title)=)[^=]+="[^"]*")*(?> src="([^"]*)")?(?> (?!title=)[^=]+="[^"]*")*( title="[^"]*")?.*)s','<img src="$2"$1$3>'],'LIVELEAK'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe width="640" height="360" src="http://www.liveleak.com/ll_embed?i=$1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>'],'URL'=>['(^[^ ]+(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s','<a href="$2"$1>'],'VIMEO'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe width="560" height="315" src="//player.vimeo.com/video/$1" allowfullscreen="" frameborder="0" scrolling="no"></iframe>'],'VINE'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe width="480" height="480" src="https://vine.co/v/$1/embed/simple" allowfullscreen="" frameborder="0" scrolling="no"></iframe><script async="" src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>'],'html:abbr'=>['(^[^ ]+(?> (?!title=)[^=]+="[^"]*")*( title="[^"]*")?.*)s','<abbr$1>'],'html:div'=>['(^[^ ]+(?> (?!class=)[^=]+="[^"]*")*( class="[^"]*")?.*)s','<div$1>'],'html:img'=>['(^[^ ]+(?> (?!(?>alt|height|src|title|width)=)[^=]+="[^"]*")*( alt="[^"]*")?(?> (?!(?>height|src|title|width)=)[^=]+="[^"]*")*( height="[^"]*")?(?> (?!(?>src|title|width)=)[^=]+="[^"]*")*( src="[^"]*")?(?> (?!(?>title|width)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!width=)[^=]+="[^"]*")*( width="[^"]*")?.*)s','<img$1$2$3$4$5>'],'html:span'=>['(^[^ ]+(?> (?!class=)[^=]+="[^"]*")*( class="[^"]*")?.*)s','<span$1>'],'html:td'=>['(^[^ ]+(?> (?!(?>col|row)span=)[^=]+="[^"]*")*( colspan="[^"]*")?(?> (?!rowspan=)[^=]+="[^"]*")*( rowspan="[^"]*")?.*)s','<td$1$2>'],'html:th'=>['(^[^ ]+(?> (?!(?>colspan|rowspan|scope)=)[^=]+="[^"]*")*( colspan="[^"]*")?(?> (?!(?>rowspan|scope)=)[^=]+="[^"]*")*( rowspan="[^"]*")?(?> (?!scope=)[^=]+="[^"]*")*( scope="[^"]*")?.*)s','<th$1$2$3>']];
	private static $attributes;
	private static $quickBranches=['/LIST'=>0,'BANDCAMP'=>1,'FP'=>2,'GROOVESHARK'=>3,'HC'=>4,'HE'=>2,'LIST'=>5,'SOUNDCLOUD'=>6,'SPOTIFY'=>7,'TWITCH'=>8,'YOUTUBE'=>9];

	protected function renderQuick($xml)
	{
		self::$attributes = [];
		$html = preg_replace_callback(
			'(<(?:((?>BANDCAMP|DAILYMOTION|F(?>P|ACEBOOK)|GROOVESHARK|H[CER]|IMG|LIVELEAK|S(?>OUNDCLOUD|POTIFY)|TWITCH|VI(?>MEO|NE)|YOUTUBE|html:(?>br|img)))(?: [^>]*)?(?:/|>.*?</\\1)|(/?(?!br/|p>)[^ />]+)[^>]*)>)',
			[$this, 'quick'],
			preg_replace(
				'(<[eis]>[^<]*</[eis]>)',
				'',
				substr($xml, 1 + strpos($xml, '>'), -4)
			)
		);

		return str_replace('<br/>', '<br>', $html);
	}

	protected function quick($m)
	{
		if (isset($m[2]))
		{
			$id = $m[2];

			if (substr($m[0], -2, 1) === '/')
			{
				$m[0] = substr($m[0], 0, -2) . '>';
				$html = $this->quick($m);

				$m[0] = '</' . $id . '>';
				$m[2] = '/' . $id;
				$html .= $this->quick($m);

				return $html;
			}
		}
		else
		{
			$id = $m[1];

			if (substr($m[0], -2, 1) === '/')
			{
				$m[0] = substr($m[0], 0, -2) . '></' . $id . '>';
				$m[1] = $id;
				unset($m[2]);

				return $this->quick($m);
			}

			$lpos = 1 + strpos($m[0], '>');
			$rpos = strrpos($m[0], '<');
			$textContent = substr($m[0], $lpos, $rpos - $lpos);

			if (strpos($textContent, '<') !== false)
				throw new \RuntimeException;

			$textContent = htmlspecialchars_decode($textContent);
		}

		if (isset(self::$static[$id]))
			return self::$static[$id];

		if (isset(self::$dynamic[$id]))
		{
			list($match, $replace) = self::$dynamic[$id];
			$html = preg_replace($match, $replace, $m[0], 1, $cnt);
			if ($cnt)
				return $html;
		}

		if (!isset(self::$quickBranches[$id]))
			return '';

		if ($id[0] !== '/')
		{
			$attributes = [];
			preg_match_all('(([^ ]+)="([^"]*))', $m[0], $matches);
			foreach ($matches[1] as $i => $attrName)
				$attributes[$attrName] = $matches[2][$i];
		}

		$qb = self::$quickBranches[$id];
		if($qb<5)if($qb<3)if($qb===0){$attributes=array_pop(self::$attributes);$html='';if(!isset($attributes['type']))$html.='</ul>';else$html.='</ol>';}elseif($qb===1){$attributes+=['track_num'=>null,'track_id'=>null];$html='<iframe width="400" height="400" allowfullscreen="" frameborder="0" scrolling="no" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if(isset($attributes['album_id'])){$html.='album='.$attributes['album_id'];if(isset($attributes['track_num']))$html.='/t='.$attributes['track_num'];}else$html.='track='.$attributes['track_id'];$html.='"></iframe>';}else{$attributes+=['char'=>null];$html=str_replace('&quot;','"',$attributes['char']);}elseif($qb===3){$attributes+=['playlistid'=>null,'songid'=>null];$html='<object type="application/x-shockwave-flash" typemustmatch="" width="400" height="'.htmlspecialchars((isset($attributes['songid'])?40:400),2).'" data="//grooveshark.com/'.htmlspecialchars((isset($attributes['songid'])?'songW':'w'),2).'idget.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="playlistID='.$attributes['playlistid'].'&amp;songID='.$attributes['songid'].'"><embed type="application/x-shockwave-flash" src="//grooveshark.com/'.htmlspecialchars((isset($attributes['songid'])?'songW':'w'),2).'idget.swf" width="400" height="'.htmlspecialchars((isset($attributes['songid'])?40:400),2).'" allowfullscreen="" flashvars="playlistID='.$attributes['playlistid'].'&amp;songID='.$attributes['songid'].'"></object>';}else{$attributes+=['content'=>null];$html='<!--'.str_replace('&quot;','"',$attributes['content']).'-->';}elseif($qb<8)if($qb===5){$html='';if(!isset($attributes['type']))$html.='<ul>';else$html.='<ol>';self::$attributes[]=$attributes;}elseif($qb===6){$attributes+=['playlist_id'=>null,'track_id'=>null,'id'=>null];$html='<iframe width="100%" height="166" style="max-width:900px" allowfullscreen="" frameborder="0" scrolling="no" src="https://w.soundcloud.com/player/?url=';if(isset($attributes['secret_token'])&&isset($attributes['playlist_id']))$html.='https://api.soundcloud.com/playlists/'.$attributes['playlist_id'].'&amp;secret_token='.$attributes['secret_token'];elseif(isset($attributes['secret_token'])&&isset($attributes['track_id']))$html.='https://api.soundcloud.com/tracks/'.$attributes['track_id'].'&amp;secret_token='.$attributes['secret_token'];else{if((strpos($attributes['id'],'://')===false))$html.='https://soundcloud.com/';$html.=$attributes['id'];if(isset($attributes['secret_token']))$html.='&amp;secret_token='.$attributes['secret_token'];}$html.='"></iframe>';}else{$attributes+=['path'=>null];$html='<iframe width="400" height="480" allowfullscreen="" frameborder="0" scrolling="no" src="https://embed.spotify.com/?view=coverart&amp;uri=';if(isset($attributes['uri']))$html.=$attributes['uri'];else$html.='spotify:'.strtr($attributes['path'],'/',':');$html.='"></iframe>';}elseif($qb===8){$attributes+=['channel'=>null];$html='<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="//www.twitch.tv/widgets/';if(isset($attributes['archive_id'])||isset($attributes['chapter_id']))$html.='arch';else$html.='l';$html.='ive_embed_player.swf"><param name="allowfullscreen" value="true"><param name="flashvars" value="channel='.$attributes['channel'];if(isset($attributes['archive_id']))$html.='&amp;archive_id='.$attributes['archive_id'];if(isset($attributes['chapter_id']))$html.='&amp;chapter_id='.$attributes['chapter_id'];$html.='&amp;auto_play=false"><embed type="application/x-shockwave-flash" width="620" height="378" allowfullscreen="" src="//www.twitch.tv/widgets/';if(isset($attributes['archive_id'])||isset($attributes['chapter_id']))$html.='arch';else$html.='l';$html.='ive_embed_player.swf" flashvars="channel='.$attributes['channel'];if(isset($attributes['archive_id']))$html.='&amp;archive_id='.$attributes['archive_id'];if(isset($attributes['chapter_id']))$html.='&amp;chapter_id='.$attributes['chapter_id'];$html.='&amp;auto_play=false"></object>';}else{$attributes+=['id'=>null,'m'=>null,'s'=>null];$html='<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/'.$attributes['id'].'?controls=2';if(isset($attributes['list']))$html.='&amp;list='.$attributes['list'];if(isset($attributes['t'])||isset($attributes['m'])){$html.='&amp;start=';if(isset($attributes['t']))$html.=$attributes['t'];elseif(isset($attributes['h']))$html.=htmlspecialchars($attributes['h']*3600+$attributes['m']*60+$attributes['s'],2);else$html.=htmlspecialchars($attributes['m']*60+$attributes['s'],2);}$html.='"></iframe>';}

		return $html;
	}
}