<?php
/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\F5;

class Renderer extends \s9e\TextFormatter\Renderer
{
	protected $htmlOutput=false;
	protected $params=['BASE_URL'=>'','IS_SIGNATURE'=>'','L_WROTE'=>'wrote:','SHOW_IMG'=>'1','SHOW_IMG_SIG'=>''];
	protected $xpath;
	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props['out'], $props['proc'], $props['source'], $props['xpath']);
		return array_keys($props);
	}
	public function renderRichText($xml)
	{
		$dom = $this->loadXML($xml);
		$this->xpath = new \DOMXPath($dom);
		$this->out = '';
		$this->at($dom->documentElement);
		unset($this->xpath);
		return $this->out;
	}
	protected function at(\DOMNode $root)
	{
		if ($root->nodeType === 3)
			$this->out .= htmlspecialchars($root->textContent,0);
		else
			foreach ($root->childNodes as $node)
			{
				$nodeName = $node->nodeName;if($nodeName==='B'){$this->out.='<strong>';$this->at($node);$this->out.='</strong>';}elseif($nodeName==='CODE'){$this->out.='<div class="codebox"><pre';if($this->xpath->evaluate('string-length(.)- string-length(translate(.,\'
\',\'\'))>28',$node))$this->out.=' class="vscroll"';$this->out.='><code>';$this->at($node);$this->out.='</code></pre></div>';}elseif($nodeName==='COLOR'){$this->out.='<span style="color: '.htmlspecialchars($node->getAttribute('color'),2).'">';$this->at($node);$this->out.='</span>';}elseif($nodeName==='DEL'||$nodeName==='EM'||$nodeName==='INS'||$nodeName==='LI'||$nodeName==='p'){$e1=htmlspecialchars(strtr($node->nodeName,'DEILMNS','deilmns'),3);$this->out.='<'.$e1.'>';$l1=strlen($this->out);$this->at($node);if($l1===strlen($this->out))$this->out=substr($this->out,0,-1).'/>';else$this->out.='</'.$e1.'>';}elseif($nodeName==='E')if($node->textContent===':)'||$node->textContent==='=)')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/smile.png" width="15" height="15" alt="smile"/>';elseif($node->textContent===':|'||$node->textContent==='=|')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/neutral.png" width="15" height="15" alt="neutral"/>';elseif($node->textContent===':('||$node->textContent==='=(')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/sad.png" width="15" height="15" alt="sad"/>';elseif($node->textContent===':D'||$node->textContent==='=D')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/big_smile.png" width="15" height="15" alt="big_smile"/>';elseif($node->textContent===':o'||$node->textContent===':O')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/yikes.png" width="15" height="15" alt="yikes"/>';elseif($node->textContent===';)')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/wink.png" width="15" height="15" alt="wink"/>';elseif($node->textContent===':/')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/hmm.png" width="15" height="15" alt="hmm"/>';elseif($node->textContent===':P'||$node->textContent===':p')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/tongue.png" width="15" height="15" alt="tongue"/>';elseif($node->textContent===':lol:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/lol.png" width="15" height="15" alt="lol"/>';elseif($node->textContent===':mad:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/mad.png" width="15" height="15" alt="mad"/>';elseif($node->textContent===':rolleyes:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/roll.png" width="15" height="15" alt="roll"/>';elseif($node->textContent===':cool:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/cool.png" width="15" height="15" alt="cool"/>';else$this->out.=htmlspecialchars($node->textContent,0);elseif($nodeName==='EMAIL'){$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}elseif($nodeName==='FORUM'){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewforum.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewforum.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}elseif($nodeName==='H'){$this->out.='<h5>';$this->at($node);$this->out.='</h5>';}elseif($nodeName==='I'){$this->out.='<em>';$this->at($node);$this->out.='</em>';}elseif($nodeName==='IMG')if(!empty($this->params['IS_SIGNATURE'])&&!empty($this->params['SHOW_IMG_SIG']))$this->out.='<img class="sigimage" src="'.htmlspecialchars($node->getAttribute('content'),2).'" alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"/>';elseif(empty($this->params['IS_SIGNATURE'])&&$this->params['SHOW_IMG']==1)$this->out.='<span class="postimg"><img src="'.htmlspecialchars($node->getAttribute('content'),2).'" alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"/></span>';else$this->at($node);elseif($nodeName==='LIST')if($node->getAttribute('type')==='1'){$this->out.='<ol class="decimal">';$this->at($node);$this->out.='</ol>';}elseif($node->getAttribute('type')==='a'){$this->out.='<ol class="alpha">';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif($nodeName==='POST'){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewtopic.php?pid='.htmlspecialchars($node->getAttribute('id'),2).'#'.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewtopic.php?pid='.htmlspecialchars($node->getAttribute('id'),0).'#'.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}elseif($nodeName==='QUOTE'){$this->out.='<div class="quotebox">';if($node->hasAttribute('author'))$this->out.='<cite>'.htmlspecialchars($node->getAttribute('author'),0).' '.htmlspecialchars($this->params['L_WROTE'],0).'</cite>';$this->out.='<blockquote><div>';$this->at($node);$this->out.='</div></blockquote></div>';}elseif($nodeName==='S'){$this->out.='<span class="bbs">';$this->at($node);$this->out.='</span>';}elseif($nodeName==='TOPIC'){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewtopic.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewtopic.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}elseif($nodeName==='U'){$this->out.='<span class="bbu">';$this->at($node);$this->out.='</span>';}elseif($nodeName==='URL'){$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'" rel="nofollow">';if($this->xpath->evaluate('text()=@url and.=@url and 55<string-length(.)- string-length(st)- string-length(et)',$node))$this->out.=htmlspecialchars($this->xpath->evaluate('substring(.,1,39)',$node),0).' … '.htmlspecialchars($this->xpath->evaluate('substring(.,string-length(.)- 10)',$node),0);else$this->at($node);$this->out.='</a>';}elseif($nodeName==='USER'){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'profile.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'profile.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}elseif($nodeName==='br')$this->out.='<br/>';elseif($nodeName==='e'||$nodeName==='i'||$nodeName==='s');else $this->at($node);
			}
	}
}