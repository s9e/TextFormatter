<?php
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\S18;

class Renderer extends \s9e\TextFormatter\Renderer
{
	protected $htmlOutput=\true;
	protected $params=['IS_GECKO'=>'','IS_IE'=>'','IS_OPERA'=>'','L_CODE'=>'Code','L_CODE_SELECT'=>'[Select]','L_QUOTE'=>'Quote','L_QUOTE_FROM'=>'Quote from','L_SEARCH_ON'=>'on','SCRIPT_URL'=>'','SMILEYS_PATH'=>''];
	protected static $tagBranches=['ABBR'=>0,'ACRONYM'=>1,'ANCHOR'=>2,'B'=>3,'BDO'=>4,'BLACK'=>5,'BLUE'=>6,'BR'=>7,'br'=>7,'CENTER'=>8,'CODE'=>9,'COLOR'=>10,'E'=>11,'EMAIL'=>12,'FLASH'=>13,'FONT'=>14,'FTP'=>15,'GLOW'=>16,'GREEN'=>17,'HR'=>18,'HTML'=>19,'NOBBC'=>19,'I'=>20,'html:em'=>20,'IMG'=>21,'IURL'=>22,'LEFT'=>23,'LI'=>24,'LIST'=>25,'LTR'=>26,'ME'=>27,'MOVE'=>28,'PRE'=>29,'html:pre'=>29,'QUOTE'=>30,'RED'=>31,'RIGHT'=>32,'RTL'=>33,'S'=>34,'html:del'=>34,'SHADOW'=>35,'SIZE'=>36,'SUB'=>37,'SUP'=>38,'TABLE'=>39,'TD'=>40,'TIME'=>41,'TR'=>42,'TT'=>43,'U'=>44,'URL'=>45,'WHITE'=>46,'e'=>47,'i'=>47,'s'=>47,'html:a'=>48,'html:b'=>49,'html:blockquote'=>50,'html:br'=>51,'html:hr'=>52,'html:i'=>53,'html:img'=>54,'html:ins'=>55,'html:s'=>56,'html:u'=>57,'p'=>58];
	protected static $btEB55FB2E=['8)'=>0,':\'['=>1,':)'=>2,':))'=>3,':-*'=>4,':-X'=>5,':-['=>6,':-\\'=>7,'::)'=>8,':D'=>9,':P'=>10,':['=>11,':o'=>12,';)'=>13,';D'=>14,'>:D'=>15,'>:['=>16,'???'=>17,'C:-)'=>18,'O0'=>19,'O:-)'=>20,'^-^'=>21];
	protected $xpath;
	public function __sleep()
	{
		$props = \get_object_vars($this);
		unset($props['out'], $props['proc'], $props['source'], $props['xpath']);
		return \array_keys($props);
	}
	public function renderRichText($xml)
	{
		if (!isset(self::$quickRenderingTest) || !\preg_match(self::$quickRenderingTest, $xml))
			try
			{
				return $this->renderQuick($xml);
			}
			catch (\Exception $e)
			{
			}
		$dom = $this->loadXML($xml);
		$this->xpath = new \DOMXPath($dom);
		$this->out = '';
		$this->at($dom->documentElement);
		$this->xpath = \null;
		return $this->out;
	}
	protected function at(\DOMNode $root)
	{
		if ($root->nodeType === 3)
			$this->out .= \htmlspecialchars($root->textContent,0);
		else
			foreach ($root->childNodes as $node)
				if (!isset(self::$tagBranches[$node->nodeName]))
					$this->at($node);
				else
				{
					$tb = self::$tagBranches[$node->nodeName];
					if($tb<30)if($tb<15)if($tb<8)if($tb<4)if($tb===0){$this->out.='<abbr title="'.\htmlspecialchars($node->getAttribute('abbr'),2).'">';$this->at($node);$this->out.='</abbr>';}elseif($tb===1){$this->out.='<acronym title="'.\htmlspecialchars($node->getAttribute('acronym'),2).'">';$this->at($node);$this->out.='</acronym>';}elseif($tb===2){$this->out.='<span id="post_'.\htmlspecialchars($node->getAttribute('anchor'),2).'">';$this->at($node);$this->out.='</span>';}else{$this->out.='<span class="bbc_bold">';$this->at($node);$this->out.='</span>';}elseif($tb===4){$this->out.='<bdo dir="'.\htmlspecialchars($node->getAttribute('bdo'),2).'">';$this->at($node);$this->out.='</bdo>';}elseif($tb===5){$this->out.='<span style="color: black;" class="bbc_color">';$this->at($node);$this->out.='</span>';}elseif($tb===6){$this->out.='<span style="color: blue;" class="bbc_color">';$this->at($node);$this->out.='</span>';}else$this->out.='<br>';elseif($tb<12)if($tb===8){$this->out.='<div align="center">';$this->at($node);$this->out.='</div>';}elseif($tb===9){$this->out.='<div class="codeheader">'.\htmlspecialchars($this->params['L_CODE'],0).':';if($node->hasAttribute('lang'))$this->out.=' ('.\htmlspecialchars($node->getAttribute('lang'),0).')';$this->out.=' <a href="#" onclick="return smfSelectText(this);" class="codeoperation">'.\htmlspecialchars($this->params['L_CODE_SELECT'],0).'</a></div>';if(!empty($this->params['IS_GECKO'])||!empty($this->params['IS_OPERA'])){$this->out.='<pre style="margin: 0; padding: 0;"><code class="bbc_code">';$this->at($node);$this->out.='</code></pre>';}else{$this->out.='<code class="bbc_code">';$this->at($node);$this->out.='</code>';}}elseif($tb===10){$this->out.='<span style="color: '.\htmlspecialchars($node->getAttribute('color'),2).';" class="bbc_color">';$this->at($node);$this->out.='</span>';}elseif(isset(self::$btEB55FB2E[$node->textContent])){$n=self::$btEB55FB2E[$node->textContent];$this->out.='<img src="'.\htmlspecialchars($this->params['SMILEYS_PATH'],2);if($n<11)if($n<6)if($n<3)if($n===0)$this->out.='cool.gif" alt="8)" title="Cool" class="smiley">';elseif($n===1)$this->out.='cry.gif" alt=":\'[" title="Cry" class="smiley">';else$this->out.='smiley.gif" alt=":)" title="Smiley" class="smiley">';elseif($n===3)$this->out.='laugh.gif" alt=":))" title="Laugh" class="smiley">';elseif($n===4)$this->out.='kiss.gif" alt=":-*" title="Kiss" class="smiley">';else$this->out.='lipsrsealed.gif" alt=":-X" title="Lips Sealed" class="smiley">';elseif($n<9)if($n===6)$this->out.='embarrassed.gif" alt=":-[" title="Embarrassed" class="smiley">';elseif($n===7)$this->out.='undecided.gif" alt=":-\\" title="Undecided" class="smiley">';else$this->out.='rolleyes.gif" alt="::)" title="Roll Eyes" class="smiley">';elseif($n===9)$this->out.='cheesy.gif" alt=":D" title="Cheesy" class="smiley">';else$this->out.='tongue.gif" alt=":P" title="Tongue" class="smiley">';elseif($n<17)if($n<14)if($n===11)$this->out.='sad.gif" alt=":[" title="Sad" class="smiley">';elseif($n===12)$this->out.='shocked.gif" alt=":o" title="Shocked" class="smiley">';else$this->out.='wink.gif" alt=";)" title="Wink" class="smiley">';elseif($n===14)$this->out.='grin.gif" alt=";D" title="Grin" class="smiley">';elseif($n===15)$this->out.='evil.gif" alt="&gt;:D" title="Evil" class="smiley">';else$this->out.='angry.gif" alt="&gt;:[" title="Angry" class="smiley">';elseif($n<20)if($n===17)$this->out.='huh.gif" alt="???" title="Huh?" class="smiley">';elseif($n===18)$this->out.='police.gif" alt="C:-)" title="Police" class="smiley">';else$this->out.='afro.gif" alt="O0" title="Afro" class="smiley">';elseif($n===20)$this->out.='angel.gif" alt="O:-)" title="Angel" class="smiley">';else$this->out.='azn.gif" alt="^-^" title="Azn" class="smiley">';}else$this->out.=\htmlspecialchars($node->textContent,0);elseif($tb===12){$this->out.='<a href="mailto:'.\htmlspecialchars($node->getAttribute('email'),2).'" class="bbc_email">';$this->at($node);$this->out.='</a>';}elseif($tb===13)if(!empty($this->params['IS_IE']))$this->out.='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.\htmlspecialchars($node->getAttribute('flash0'),2).'" height="'.\htmlspecialchars($node->getAttribute('flash1'),2).'"><param name="movie" value="'.\htmlspecialchars($node->getAttribute('content'),2).'"><param name="play" value="true"><param name="loop" value="true"><param name="quality" value="high"><param name="AllowScriptAccess" value="never"><embed src="'.\htmlspecialchars($node->getAttribute('content'),2).'" width="'.\htmlspecialchars($node->getAttribute('flash0'),2).'" height="'.\htmlspecialchars($node->getAttribute('flash1'),2).'" play="true" loop="true" quality="high" allowscriptaccess="never"><noembed><a href="'.\htmlspecialchars($node->getAttribute('content'),2).'" target="_blank" class="new_win">'.\htmlspecialchars($node->getAttribute('content'),0).'</a></noembed></object>';else$this->out.='<embed type="application/x-shockwave-flash" src="'.\htmlspecialchars($node->getAttribute('content'),2).'" width="'.\htmlspecialchars($node->getAttribute('flash0'),2).'" height="'.\htmlspecialchars($node->getAttribute('flash1'),2).'" play="true" loop="true" quality="high" allowscriptaccess="never"><noembed><a href="'.\htmlspecialchars($node->getAttribute('content'),2).'" target="_blank" class="new_win">'.\htmlspecialchars($node->getAttribute('content'),0).'</a></noembed>';else{$this->out.='<span style="font-family: '.\htmlspecialchars($node->getAttribute('font'),2).';" class="bbc_font">';$this->at($node);$this->out.='</span>';}elseif($tb<23)if($tb<19)if($tb===15){$this->out.='<a href="'.\htmlspecialchars($node->getAttribute('ftp'),2).'" class="bbc_ftp new_win" target="_blank">';$this->at($node);$this->out.='</a>';}elseif($tb===16)if(!empty($this->params['IS_IE'])){$this->out.='<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Glow(color='.\htmlspecialchars($node->getAttribute('glow0'),2).', strength='.\htmlspecialchars($node->getAttribute('glow1'),2).'); font: inherit;">';$this->at($node);$this->out.='</td></tr></table>';}else{$this->out.='<span style="text-shadow: '.\htmlspecialchars($node->getAttribute('glow0'),2).' 1px 1px 1px">';$this->at($node);$this->out.='</span>';}elseif($tb===17){$this->out.='<span style="color: green;" class="bbc_color">';$this->at($node);$this->out.='</span>';}else$this->out.='<hr>';elseif($tb===19)$this->at($node);elseif($tb===20){$this->out.='<em>';$this->at($node);$this->out.='</em>';}elseif($tb===21){$this->out.='<img src="'.\htmlspecialchars($node->getAttribute('src'),2).'"';if($node->hasAttribute('alt'))$this->out.=' alt="'.\htmlspecialchars($node->getAttribute('alt'),2).'"';if($node->hasAttribute('height'))$this->out.=' height="'.\htmlspecialchars($node->getAttribute('height'),2).'"';if($node->hasAttribute('width'))$this->out.=' width="'.\htmlspecialchars($node->getAttribute('width'),2).'"';$this->out.=' class="bbc_img';if($node->hasAttribute('height')||$node->hasAttribute('width'))$this->out.=' resized';$this->out.='">';}else{$this->out.='<a href="'.\htmlspecialchars($node->getAttribute('iurl'),2).'" class="bbc_link">';$this->at($node);$this->out.='</a>';}elseif($tb<27)if($tb===23){$this->out.='<div style="text-align: left;">';$this->at($node);$this->out.='</div>';}elseif($tb===24){$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($tb===25){if($node->hasAttribute('type')){$this->out.='<ul class="bbc_list" style="list-style-type: '.\htmlspecialchars($node->getAttribute('type'),2).';">';$this->at($node);}else{$this->out.='<ul class="bbc_list">';$this->at($node);}$this->out.='</ul>';}else{$this->out.='<div dir="ltr">';$this->at($node);$this->out.='</div>';}elseif($tb===27){$this->out.='<div class="meaction">* '.\htmlspecialchars($node->getAttribute('me'),0).' ';$this->at($node);$this->out.='</div>';}elseif($tb===28){$this->out.='<marquee>';$this->at($node);$this->out.='</marquee>';}else{$this->out.='<pre>';$this->at($node);$this->out.='</pre>';}elseif($tb<45)if($tb<38)if($tb<34)if($tb===30){$this->out.='<div class="quoteheader"><div class="topslice_quote">';if(!$node->hasAttribute('author'))$this->out.=\htmlspecialchars($this->params['L_QUOTE'],0);elseif($node->hasAttribute('date')&&$node->hasAttribute('link'))$this->out.='<a href="'.\htmlspecialchars($this->params['SCRIPT_URL'],2).'?'.\htmlspecialchars($node->getAttribute('link'),2).'">'.\htmlspecialchars($this->params['L_QUOTE_FROM'],0).': '.\htmlspecialchars($node->getAttribute('author'),0).' '.\htmlspecialchars($this->params['L_SEARCH_ON'],0).' '.\htmlspecialchars($node->getAttribute('date'),0).'</a>';else$this->out.=\htmlspecialchars($this->params['L_QUOTE_FROM'],0).': '.\htmlspecialchars($node->getAttribute('author'),0);$this->out.='</div></div><blockquote>';$this->at($node);$this->out.='</blockquote><div class="quotefooter"><div class="botslice_quote"></div></div>';}elseif($tb===31){$this->out.='<span style="color: red;" class="bbc_color">';$this->at($node);$this->out.='</span>';}elseif($tb===32){$this->out.='<div style="text-align: right;">';$this->at($node);$this->out.='</div>';}else{$this->out.='<div dir="rtl">';$this->at($node);$this->out.='</div>';}elseif($tb===34){$this->out.='<del>';$this->at($node);$this->out.='</del>';}elseif($tb===35){$this->out.='<span style="';if(!empty($this->params['IS_IE'])){$this->out.='display: inline-block; filter: Shadow(color='.\htmlspecialchars($node->getAttribute('color'),2).', direction=';if($node->getAttribute('direction')==='left')$this->out.='270';elseif($node->getAttribute('direction')==='right')$this->out.='90';elseif($node->getAttribute('direction')==='top')$this->out.='0';elseif($node->getAttribute('direction')==='bottom')$this->out.='180';else$this->out.=\htmlspecialchars($node->getAttribute('direction'),2);$this->out.='); height: 1.2em;';}else{$this->out.='text-shadow: '.\htmlspecialchars($node->getAttribute('color'),2).' ';if($this->xpath->evaluate('@direction=\'top\'or@direction<50',$node))$this->out.='0 -2px 1px';elseif($this->xpath->evaluate('@direction=\'right\'or@direction<100',$node))$this->out.='2px 0 1px';elseif($this->xpath->evaluate('@direction=\'bottom\'or@direction<190',$node))$this->out.='0 2px 1px';elseif($this->xpath->evaluate('@direction=\'left\'or@direction<280',$node))$this->out.='-2px 0 1px';else$this->out.='1px 1px 1px';}$this->out.='">';$this->at($node);$this->out.='</span>';}elseif($tb===36){$this->out.='<span style="font-size: '.\htmlspecialchars($node->getAttribute('size'),2).';" class="bbc_size">';$this->at($node);$this->out.='</span>';}else{$this->out.='<sub>';$this->at($node);$this->out.='</sub>';}elseif($tb<42)if($tb===38){$this->out.='<sup>';$this->at($node);$this->out.='</sup>';}elseif($tb===39){$this->out.='<table class="bbc_table">';$this->at($node);$this->out.='</table>';}elseif($tb===40){$this->out.='<td>';$this->at($node);$this->out.='</td>';}else$this->out.=\htmlspecialchars($node->getAttribute('time'),0);elseif($tb===42){$this->out.='<tr>';$this->at($node);$this->out.='</tr>';}elseif($tb===43){$this->out.='<span class="bbc_tt">';$this->at($node);$this->out.='</span>';}else{$this->out.='<span class="bbc_u">';$this->at($node);$this->out.='</span>';}elseif($tb<52)if($tb<49){if($tb===45){$this->out.='<a href="'.\htmlspecialchars($node->getAttribute('url'),2).'" class="bbc_link" target="_blank">';$this->at($node);$this->out.='</a>';}elseif($tb===46){$this->out.='<span style="color: white;" class="bbc_color">';$this->at($node);$this->out.='</span>';}elseif($tb===47);else{$this->out.='<a';if($node->hasAttribute('href'))$this->out.=' href="'.\htmlspecialchars($node->getAttribute('href'),2).'"';$this->out.='>';$this->at($node);$this->out.='</a>';}}elseif($tb===49){$this->out.='<b>';$this->at($node);$this->out.='</b>';}elseif($tb===50){$this->out.='<blockquote>';$this->at($node);$this->out.='</blockquote>';}else$this->out.='<br>';elseif($tb<56)if($tb===52)$this->out.='<hr>';elseif($tb===53){$this->out.='<i>';$this->at($node);$this->out.='</i>';}elseif($tb===54){$this->out.='<img';if($node->hasAttribute('alt'))$this->out.=' alt="'.\htmlspecialchars($node->getAttribute('alt'),2).'"';if($node->hasAttribute('height'))$this->out.=' height="'.\htmlspecialchars($node->getAttribute('height'),2).'"';if($node->hasAttribute('src'))$this->out.=' src="'.\htmlspecialchars($node->getAttribute('src'),2).'"';if($node->hasAttribute('width'))$this->out.=' width="'.\htmlspecialchars($node->getAttribute('width'),2).'"';$this->out.='>';}else{$this->out.='<ins>';$this->at($node);$this->out.='</ins>';}elseif($tb===56){$this->out.='<s>';$this->at($node);$this->out.='</s>';}elseif($tb===57){$this->out.='<u>';$this->at($node);$this->out.='</u>';}else{$this->out.='<p>';$this->at($node);$this->out.='</p>';}
				}
	}
	private static $static=['/ABBR'=>'</abbr>','/ACRONYM'=>'</acronym>','/ANCHOR'=>'</span>','/B'=>'</span>','/BDO'=>'</bdo>','/BLACK'=>'</span>','/BLUE'=>'</span>','/CENTER'=>'</div>','/COLOR'=>'</span>','/EMAIL'=>'</a>','/FONT'=>'</span>','/FTP'=>'</a>','/GREEN'=>'</span>','/HTML'=>'','/I'=>'</em>','/IURL'=>'</a>','/LEFT'=>'</div>','/LI'=>'</li>','/LTR'=>'</div>','/ME'=>'</div>','/MOVE'=>'</marquee>','/NOBBC'=>'','/PRE'=>'</pre>','/QUOTE'=>'</blockquote><div class="quotefooter"><div class="botslice_quote"></div></div>','/RED'=>'</span>','/RIGHT'=>'</div>','/RTL'=>'</div>','/S'=>'</del>','/SIZE'=>'</span>','/SUB'=>'</sub>','/SUP'=>'</sup>','/TABLE'=>'</table>','/TD'=>'</td>','/TR'=>'</tr>','/TT'=>'</span>','/U'=>'</span>','/URL'=>'</a>','/WHITE'=>'</span>','/html:a'=>'</a>','/html:b'=>'</b>','/html:blockquote'=>'</blockquote>','/html:del'=>'</del>','/html:em'=>'</em>','/html:i'=>'</i>','/html:ins'=>'</ins>','/html:pre'=>'</pre>','/html:s'=>'</s>','/html:u'=>'</u>','B'=>'<span class="bbc_bold">','BLACK'=>'<span style="color: black;" class="bbc_color">','BLUE'=>'<span style="color: blue;" class="bbc_color">','BR'=>'<br>','CENTER'=>'<div align="center">','GREEN'=>'<span style="color: green;" class="bbc_color">','HR'=>'<hr>','HTML'=>'','I'=>'<em>','LEFT'=>'<div style="text-align: left;">','LI'=>'<li>','LTR'=>'<div dir="ltr">','MOVE'=>'<marquee>','NOBBC'=>'','PRE'=>'<pre>','RED'=>'<span style="color: red;" class="bbc_color">','RIGHT'=>'<div style="text-align: right;">','RTL'=>'<div dir="rtl">','S'=>'<del>','SUB'=>'<sub>','SUP'=>'<sup>','TABLE'=>'<table class="bbc_table">','TD'=>'<td>','TR'=>'<tr>','TT'=>'<span class="bbc_tt">','U'=>'<span class="bbc_u">','WHITE'=>'<span style="color: white;" class="bbc_color">','html:b'=>'<b>','html:blockquote'=>'<blockquote>','html:br'=>'<br>','html:del'=>'<del>','html:em'=>'<em>','html:hr'=>'<hr>','html:i'=>'<i>','html:ins'=>'<ins>','html:pre'=>'<pre>','html:s'=>'<s>','html:u'=>'<u>'];
	private static $dynamic=['ABBR'=>['(^[^ ]+(?> (?!abbr=)[^=]+="[^"]*")*(?> abbr="([^"]*)")?.*)s','<abbr title="$1">'],'ACRONYM'=>['(^[^ ]+(?> (?!acronym=)[^=]+="[^"]*")*(?> acronym="([^"]*)")?.*)s','<acronym title="$1">'],'ANCHOR'=>['(^[^ ]+(?> (?!anchor=)[^=]+="[^"]*")*(?> anchor="([^"]*)")?.*)s','<span id="post_$1">'],'BDO'=>['(^[^ ]+(?> (?!bdo=)[^=]+="[^"]*")*(?> bdo="([^"]*)")?.*)s','<bdo dir="$1">'],'COLOR'=>['(^[^ ]+(?> (?!color=)[^=]+="[^"]*")*(?> color="([^"]*)")?.*)s','<span style="color: $1;" class="bbc_color">'],'EMAIL'=>['(^[^ ]+(?> (?!email=)[^=]+="[^"]*")*(?> email="([^"]*)")?.*)s','<a href="mailto:$1" class="bbc_email">'],'FONT'=>['(^[^ ]+(?> (?!font=)[^=]+="[^"]*")*(?> font="([^"]*)")?.*)s','<span style="font-family: $1;" class="bbc_font">'],'FTP'=>['(^[^ ]+(?> (?!ftp=)[^=]+="[^"]*")*(?> ftp="([^"]*)")?.*)s','<a href="$1" class="bbc_ftp new_win" target="_blank">'],'IURL'=>['(^[^ ]+(?> (?!iurl=)[^=]+="[^"]*")*(?> iurl="([^"]*)")?.*)s','<a href="$1" class="bbc_link">'],'SIZE'=>['(^[^ ]+(?> (?!size=)[^=]+="[^"]*")*(?> size="([^"]*)")?.*)s','<span style="font-size: $1;" class="bbc_size">'],'URL'=>['(^[^ ]+(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s','<a href="$1" class="bbc_link" target="_blank">'],'html:a'=>['(^[^ ]+(?> (?!href=)[^=]+="[^"]*")*( href="[^"]*")?.*)s','<a$1>'],'html:img'=>['(^[^ ]+(?> (?!(?>alt|height|src|width)=)[^=]+="[^"]*")*( alt="[^"]*")?(?> (?!(?>height|src|width)=)[^=]+="[^"]*")*( height="[^"]*")?(?> (?!(?>src|width)=)[^=]+="[^"]*")*( src="[^"]*")?(?> (?!width=)[^=]+="[^"]*")*( width="[^"]*")?.*)s','<img$1$2$3$4>']];
	private static $attributes;
	private static $quickBranches=['/CODE'=>0,'/GLOW'=>1,'/LIST'=>2,'CODE'=>3,'E'=>4,'FLASH'=>5,'GLOW'=>6,'IMG'=>7,'LIST'=>8,'ME'=>9,'QUOTE'=>10,'TIME'=>11];
	public static $quickRenderingTest='(<SHADOW[ />])';

	protected function renderQuick($xml)
	{
		self::$attributes = [];
		$html = \preg_replace_callback(
			'(<(?:((?>E|FLASH|IMG|TIME|html:(?>img|[bh]r)|[BH]R))(?: [^>]*)?(?:/|>.*?</\\1)|(/?(?!br/|p>)[^ />]+)[^>]*)>)',
			[$this, 'quick'],
			\preg_replace(
				'(<[eis]>[^<]*</[eis]>)',
				'',
				\substr($xml, 1 + \strpos($xml, '>'), -4)
			)
		);

		return \str_replace('<br/>', '<br>', $html);
	}

	protected function quick($m)
	{
		if (isset($m[2]))
		{
			$id = $m[2];

			if (\substr($m[0], -2, 1) === '/')
			{
				$m[0] = \substr($m[0], 0, -2) . '>';
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

			if (\substr($m[0], -2, 1) === '/')
			{
				$m[0] = \substr($m[0], 0, -2) . '></' . $id . '>';
				$m[1] = $id;
				unset($m[2]);

				return $this->quick($m);
			}

			$lpos = 1 + \strpos($m[0], '>');
			$rpos = \strrpos($m[0], '<');
			$textContent = \substr($m[0], $lpos, $rpos - $lpos);

			if (\strpos($textContent, '<') !== \false)
				throw new \RuntimeException;

			$textContent = \htmlspecialchars_decode($textContent);
		}

		if (isset(self::$static[$id]))
			return self::$static[$id];

		if (isset(self::$dynamic[$id]))
		{
			list($match, $replace) = self::$dynamic[$id];
			$html = \preg_replace($match, $replace, $m[0], 1, $cnt);
			if ($cnt)
				return $html;
		}

		if (!isset(self::$quickBranches[$id]))
		{
			if (\preg_match('(^/?SHADOW)', $id))
				throw new \RuntimeException;
			return '';
		}

		if ($id[0] !== '/')
		{
			$attributes = [];
			\preg_match_all('(([^ ]+)="([^"]*))', $m[0], $matches);
			foreach ($matches[1] as $i => $attrName)
				$attributes[$attrName] = $matches[2][$i];
		}

		$qb = self::$quickBranches[$id];
		if($qb<6)if($qb<3)if($qb===0){$html='';if(!empty($this->params['IS_GECKO'])||!empty($this->params['IS_OPERA']))$html.='</code></pre>';else$html.='</code>';}elseif($qb===1){$html='';if(!empty($this->params['IS_IE']))$html.='</td></tr></table>';else$html.='</span>';}else{$attributes=\array_pop(self::$attributes);$html='';if(isset($attributes['type']));else;$html.='</ul>';}elseif($qb===3){$html='<div class="codeheader">'.\htmlspecialchars($this->params['L_CODE'],0).':';if(isset($attributes['lang']))$html.=' ('.\str_replace('&quot;','"',$attributes['lang']).')';$html.=' <a href="#" onclick="return smfSelectText(this);" class="codeoperation">'.\htmlspecialchars($this->params['L_CODE_SELECT'],0).'</a></div>';if(!empty($this->params['IS_GECKO'])||!empty($this->params['IS_OPERA']))$html.='<pre style="margin: 0; padding: 0;"><code class="bbc_code">';else$html.='<code class="bbc_code">';}elseif($qb===4){$html='';if(isset(self::$btEB55FB2E[$textContent])){$n=self::$btEB55FB2E[$textContent];$html.='<img src="'.\htmlspecialchars($this->params['SMILEYS_PATH'],2);if($n<11)if($n<6)if($n<3)if($n===0)$html.='cool.gif" alt="8)" title="Cool" class="smiley">';elseif($n===1)$html.='cry.gif" alt=":\'[" title="Cry" class="smiley">';else$html.='smiley.gif" alt=":)" title="Smiley" class="smiley">';elseif($n===3)$html.='laugh.gif" alt=":))" title="Laugh" class="smiley">';elseif($n===4)$html.='kiss.gif" alt=":-*" title="Kiss" class="smiley">';else$html.='lipsrsealed.gif" alt=":-X" title="Lips Sealed" class="smiley">';elseif($n<9)if($n===6)$html.='embarrassed.gif" alt=":-[" title="Embarrassed" class="smiley">';elseif($n===7)$html.='undecided.gif" alt=":-\\" title="Undecided" class="smiley">';else$html.='rolleyes.gif" alt="::)" title="Roll Eyes" class="smiley">';elseif($n===9)$html.='cheesy.gif" alt=":D" title="Cheesy" class="smiley">';else$html.='tongue.gif" alt=":P" title="Tongue" class="smiley">';elseif($n<17)if($n<14)if($n===11)$html.='sad.gif" alt=":[" title="Sad" class="smiley">';elseif($n===12)$html.='shocked.gif" alt=":o" title="Shocked" class="smiley">';else$html.='wink.gif" alt=";)" title="Wink" class="smiley">';elseif($n===14)$html.='grin.gif" alt=";D" title="Grin" class="smiley">';elseif($n===15)$html.='evil.gif" alt="&gt;:D" title="Evil" class="smiley">';else$html.='angry.gif" alt="&gt;:[" title="Angry" class="smiley">';elseif($n<20)if($n===17)$html.='huh.gif" alt="???" title="Huh?" class="smiley">';elseif($n===18)$html.='police.gif" alt="C:-)" title="Police" class="smiley">';else$html.='afro.gif" alt="O0" title="Afro" class="smiley">';elseif($n===20)$html.='angel.gif" alt="O:-)" title="Angel" class="smiley">';else$html.='azn.gif" alt="^-^" title="Azn" class="smiley">';}else$html.=\htmlspecialchars($textContent,0);}else{$attributes+=['flash0'=>\null,'flash1'=>\null,'content'=>\null];$html='';if(!empty($this->params['IS_IE']))$html.='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$attributes['flash0'].'" height="'.$attributes['flash1'].'"><param name="movie" value="'.$attributes['content'].'"><param name="play" value="true"><param name="loop" value="true"><param name="quality" value="high"><param name="AllowScriptAccess" value="never"><embed src="'.$attributes['content'].'" width="'.$attributes['flash0'].'" height="'.$attributes['flash1'].'" play="true" loop="true" quality="high" allowscriptaccess="never"><noembed><a href="'.$attributes['content'].'" target="_blank" class="new_win">'.\str_replace('&quot;','"',$attributes['content']).'</a></noembed></object>';else$html.='<embed type="application/x-shockwave-flash" src="'.$attributes['content'].'" width="'.$attributes['flash0'].'" height="'.$attributes['flash1'].'" play="true" loop="true" quality="high" allowscriptaccess="never"><noembed><a href="'.$attributes['content'].'" target="_blank" class="new_win">'.\str_replace('&quot;','"',$attributes['content']).'</a></noembed>';}elseif($qb<9)if($qb===6){$attributes+=['glow0'=>\null,'glow1'=>\null];$html='';if(!empty($this->params['IS_IE']))$html.='<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Glow(color='.$attributes['glow0'].', strength='.$attributes['glow1'].'); font: inherit;">';else$html.='<span style="text-shadow: '.$attributes['glow0'].' 1px 1px 1px">';}elseif($qb===7){$attributes+=['src'=>\null];$html='<img src="'.$attributes['src'].'"';if(isset($attributes['alt']))$html.=' alt="'.$attributes['alt'].'"';if(isset($attributes['height']))$html.=' height="'.$attributes['height'].'"';if(isset($attributes['width']))$html.=' width="'.$attributes['width'].'"';$html.=' class="bbc_img';if(isset($attributes['height'])||isset($attributes['width']))$html.=' resized';$html.='">';}else{$html='';if(isset($attributes['type']))$html.='<ul class="bbc_list" style="list-style-type: '.$attributes['type'].';">';else$html.='<ul class="bbc_list">';self::$attributes[]=$attributes;}elseif($qb===9){$attributes+=['me'=>\null];$html='<div class="meaction">* '.\str_replace('&quot;','"',$attributes['me']).' ';}elseif($qb===10){$attributes+=['link'=>\null,'author'=>\null];$html='<div class="quoteheader"><div class="topslice_quote">';if(!isset($attributes['author']))$html.=\htmlspecialchars($this->params['L_QUOTE'],0);elseif(isset($attributes['date'])&&isset($attributes['link']))$html.='<a href="'.\htmlspecialchars($this->params['SCRIPT_URL'],2).'?'.$attributes['link'].'">'.\htmlspecialchars($this->params['L_QUOTE_FROM'],0).': '.\str_replace('&quot;','"',$attributes['author']).' '.\htmlspecialchars($this->params['L_SEARCH_ON'],0).' '.\str_replace('&quot;','"',$attributes['date']).'</a>';else$html.=\htmlspecialchars($this->params['L_QUOTE_FROM'],0).': '.\str_replace('&quot;','"',$attributes['author']);$html.='</div></div><blockquote>';}else{$attributes+=['time'=>\null];$html=\str_replace('&quot;','"',$attributes['time']);}

		return $html;
	}
}