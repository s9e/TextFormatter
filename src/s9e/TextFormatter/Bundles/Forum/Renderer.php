<?php
/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\Forum;

class Renderer extends \s9e\TextFormatter\Renderer
{
	protected $htmlOutput=true;
	protected $dynamicParams=[];
	protected $params=['EMOTICONS_PATH'=>'','L_HIDE'=>'Hide','L_SHOW'=>'Show','L_SPOILER'=>'Spoiler','L_WROTE'=>'wrote:'];
	protected $xpath;
	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props["out"], $props["proc"], $props["source"], $props["xpath"]);

		return array_keys($props);
	}
	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
		unset($this->dynamicParams[$paramName]);
	}
	public function renderRichText($xml)
	{
		$dom = $this->loadXML($xml);
		$this->xpath = new \DOMXPath($dom);
		$this->out = "";
		$this->at($dom->documentElement);

		return $this->out;
	}
	protected function at($root, $xpath = null)
	{
		if ($root->nodeType === 3)
		{
			$this->out .= htmlspecialchars($root->textContent,0);
		}
		else
		{
			foreach ($root->childNodes as $node)
			{
				$nodeName = $node->nodeName;if($nodeName==='YOUTUBE'){$this->out.='<iframe width="560" height="315" src="http://www.youtube.com/embed/'.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen=""></iframe>';}elseif($nodeName==='VIMEO'){$this->out.='<iframe width="560" height="315" src="http://player.vimeo.com/video/'.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen=""></iframe>';}elseif($nodeName==='TWITCH'){$this->out.='<object type="application/x-shockwave-flash" typemustmatch="" width="620" height="378" data="http://www.twitch.tv/widgets/'.htmlspecialchars((($node->hasAttribute('archive_id')||$node->hasAttribute('chapter_id'))?'arch':'l'),2).'ive_embed_player.swf"><param name="flashvars" value="channel='.htmlspecialchars($node->getAttribute('channel'),2);if($node->hasAttribute('archive_id')){$this->out.='&amp;archive_id='.htmlspecialchars($node->getAttribute('archive_id'),2);}if($node->hasAttribute('chapter_id')){$this->out.='&amp;chapter_id='.htmlspecialchars($node->getAttribute('chapter_id'),2);}$this->out.='"><embed type="application/x-shockwave-flash" width="620" height="378" src="http://www.twitch.tv/widgets/'.htmlspecialchars((($node->hasAttribute('archive_id')||$node->hasAttribute('chapter_id'))?'arch':'l'),2).'ive_embed_player.swf"></object>';}elseif($nodeName==='LIVELEAK'){$this->out.='<iframe width="560" height="315" src="http://www.liveleak.com/e/'.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen=""></iframe>';}elseif($nodeName==='FACEBOOK'){$this->out.='<iframe width="560" height="315" src="https://www.facebook.com/video/embed?video_id='.htmlspecialchars($node->getAttribute('id'),2).'" allowfullscreen=""></iframe>';}elseif($nodeName==='DAILYMOTION'){$this->out.='<object type="application/x-shockwave-flash" typemustmatch="" width="560" height="315" data="http://www.dailymotion.com/swf/'.htmlspecialchars($node->getAttribute('id'),2).'"><param name="allowFullScreen" value="true"><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/'.htmlspecialchars($node->getAttribute('id'),2).'" width="560" height="315" allowfullscreen=""></object>';}elseif($nodeName==='E'){if($node->textContent===':)'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/smile.png" alt=":)">';}elseif($node->textContent===':-)'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/smile.png" alt=":-)">';}elseif($node->textContent===';)'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/wink.png" alt=";)">';}elseif($node->textContent===';-)'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/wink.png" alt=";-)">';}elseif($node->textContent===':D'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/grin.png" alt=":D">';}elseif($node->textContent===':-D'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/grin.png" alt=":-D">';}elseif($node->textContent===':('){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/frown.png" alt=":(">';}elseif($node->textContent===':-('){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/frown.png" alt=":-(">';}elseif($node->textContent===':-*'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/kiss.png" alt=":-*">';}elseif($node->textContent===':P'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/razz.png" alt=":P">';}elseif($node->textContent===':-P'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/razz.png" alt=":-P">';}elseif($node->textContent===':p'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/razz.png" alt=":p">';}elseif($node->textContent===':-p'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/razz.png" alt=":-p">';}elseif($node->textContent===':?'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/confused.png" alt=":?">';}elseif($node->textContent===':-?'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/confused.png" alt=":-?">';}elseif($node->textContent===':|'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/neutral.png" alt=":|">';}elseif($node->textContent===':-|'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/neutral.png" alt=":-|">';}elseif($node->textContent===':o'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/shock.png" alt=":o">';}elseif($node->textContent===':lol:'){$this->out.='<img src="'.htmlspecialchars($this->params['EMOTICONS_PATH'],2).'/laugh.png" alt=":lol:">';}else{$this->out.=htmlspecialchars($node->textContent,0);}}elseif($nodeName==='URL'){$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'"';if($node->hasAttribute('title')){$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';}$this->out.='>';$this->at($node);$this->out.='</a>';}elseif($nodeName==='U'){$this->out.='<u>';$this->at($node);$this->out.='</u>';}elseif($nodeName==='SPOILER'){$this->out.='<div class="spoiler"><div class="spoiler-header"><button onclick="var c=this.parentNode.nextSibling.style,s=this.firstChild.style,h=this.lastChild.style;\'\'!=c.display?(c.display=h.display=\'\',s.display=\'none\'):(c.display=h.display=\'none\',s.display=\'\')"><span>'.htmlspecialchars($this->params['L_SHOW'],0).'</span><span style="display:none">'.htmlspecialchars($this->params['L_HIDE'],0).'</span></button><span class="spoiler-title">'.htmlspecialchars($this->params['L_SPOILER'],0).' '.htmlspecialchars($node->getAttribute('title'),0).'</span></div><div class="spoiler-content" style="display:none">';$this->at($node);$this->out.='</div></div>';}elseif($nodeName==='SIZE'){$this->out.='<span style="font-size:'.htmlspecialchars($node->getAttribute('size'),2).'px">';$this->at($node);$this->out.='</span>';}elseif($nodeName==='S'){$this->out.='<s>';$this->at($node);$this->out.='</s>';}elseif($nodeName==='QUOTE'){$this->out.='<blockquote';if(!$node->hasAttribute('author')){$this->out.=' class="uncited"';}$this->out.='><div>';if($node->hasAttribute('author')){$this->out.='<cite>'.htmlspecialchars($node->getAttribute('author'),0).' '.htmlspecialchars($this->params['L_WROTE'],0).'</cite>';}$this->at($node);$this->out.='</div></blockquote>';}elseif($nodeName==='LI'){$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($nodeName==='LIST'){if(!$node->hasAttribute('type')){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif(strpos('upperlowerdecim',substr($node->getAttribute('type'),0,5))!==false){$this->out.='<ol style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ul>';}}elseif($nodeName==='I'){$this->out.='<i>';$this->at($node);$this->out.='</i>';}elseif($nodeName==='EMAIL'){$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}elseif($nodeName==='COLOR'){$this->out.='<span style="color:'.htmlspecialchars($node->getAttribute('color'),2).'">';$this->at($node);$this->out.='</span>';}elseif($nodeName==='CODE'){$this->out.='<pre data-s9e-livepreview-postprocess="if(\'undefined\'!==typeof hljs){var a=this.innerHTML;a in hljs._?this.innerHTML=hljs._[a]:(Object.keys&amp;&amp;7&lt;Object.keys(hljs._).length&amp;&amp;(hljs._={}),hljs.highlightBlock(this.firstChild),hljs._[a]=this.innerHTML)};"><code class="'.htmlspecialchars($node->getAttribute('lang'),2).'">';$this->at($node);$this->out.='</code></pre>';if($this->xpath->evaluate('not(following::CODE)',$node)){$this->out.='<script>if("undefined"===typeof hljs){var a=document.getElementsByTagName("head")[0],b=document.createElement("link");b.type="text/css";b.rel="stylesheet";b.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/styles/github.min.css";a.appendChild(b);b=document.createElement("script");b.type="text/javascript";b.onload=function(){hljs._={};hljs.initHighlighting()};b.async=!0;b.src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.3/highlight.min.js";a.appendChild(b)};</script>';}}elseif($nodeName==='B'){$this->out.='<b>';$this->at($node);$this->out.='</b>';}elseif($nodeName==='et'||$nodeName==='i'||$nodeName==='st'){}elseif($nodeName==='br'){$this->out.='<br>';}elseif($nodeName==='p'){$this->out.='<p>';$this->at($node);$this->out.='</p>';}else $this->at($node);
			}
		}
	}
}