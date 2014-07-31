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
	protected static $tagBranches=['B'=>0,'CODE'=>1,'COLOR'=>2,'DEL'=>3,'E'=>4,'EM'=>5,'I'=>5,'EMAIL'=>6,'FORUM'=>7,'H'=>8,'IMG'=>9,'INS'=>10,'LI'=>11,'LIST'=>12,'POST'=>13,'QUOTE'=>14,'S'=>15,'TOPIC'=>16,'U'=>17,'URL'=>18,'USER'=>19,'br'=>20,'e'=>21,'i'=>21,'s'=>21,'p'=>22];
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
		$this->xpath = null;
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
					if($tb<12)if($tb<6)if($tb<3)if($tb===0){$this->out.='<strong>';$this->at($node);$this->out.='</strong>';}elseif($tb===1){$this->out.='<div class="codebox"><pre';if($this->xpath->evaluate('string-length(.)- string-length(translate(.,\'
\',\'\'))>28',$node))$this->out.=' class="vscroll"';$this->out.='><code>';$this->at($node);$this->out.='</code></pre></div>';}else{$this->out.='<span style="color: '.htmlspecialchars($node->getAttribute('color'),2).'">';$this->at($node);$this->out.='</span>';}elseif($tb===3){$this->out.='<del>';$this->at($node);$this->out.='</del>';}elseif($tb===4)if($node->textContent===':)'||$node->textContent==='=)')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/smile.png" width="15" height="15" alt="smile"/>';elseif($node->textContent===':|'||$node->textContent==='=|')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/neutral.png" width="15" height="15" alt="neutral"/>';elseif($node->textContent===':('||$node->textContent==='=(')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/sad.png" width="15" height="15" alt="sad"/>';elseif($node->textContent===':D'||$node->textContent==='=D')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/big_smile.png" width="15" height="15" alt="big_smile"/>';elseif($node->textContent===':o'||$node->textContent===':O')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/yikes.png" width="15" height="15" alt="yikes"/>';elseif($node->textContent===';)')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/wink.png" width="15" height="15" alt="wink"/>';elseif($node->textContent===':/')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/hmm.png" width="15" height="15" alt="hmm"/>';elseif($node->textContent===':P'||$node->textContent===':p')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/tongue.png" width="15" height="15" alt="tongue"/>';elseif($node->textContent===':lol:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/lol.png" width="15" height="15" alt="lol"/>';elseif($node->textContent===':mad:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/mad.png" width="15" height="15" alt="mad"/>';elseif($node->textContent===':rolleyes:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/roll.png" width="15" height="15" alt="roll"/>';elseif($node->textContent===':cool:')$this->out.='<img src="'.htmlspecialchars($this->params['BASE_URL'],2).'img/smilies/cool.png" width="15" height="15" alt="cool"/>';else$this->out.=htmlspecialchars($node->textContent,0);else{$this->out.='<em>';$this->at($node);$this->out.='</em>';}elseif($tb<9)if($tb===6){$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}elseif($tb===7){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewforum.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewforum.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}else{$this->out.='<h5>';$this->at($node);$this->out.='</h5>';}elseif($tb===9)if(!empty($this->params['IS_SIGNATURE'])&&!empty($this->params['SHOW_IMG_SIG']))$this->out.='<img class="sigimage" src="'.htmlspecialchars($node->getAttribute('content'),2).'" alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"/>';elseif(empty($this->params['IS_SIGNATURE'])&&$this->params['SHOW_IMG']==1)$this->out.='<span class="postimg"><img src="'.htmlspecialchars($node->getAttribute('content'),2).'" alt="'.htmlspecialchars($node->getAttribute('alt'),2).'"/></span>';else$this->at($node);elseif($tb===10){$this->out.='<ins>';$this->at($node);$this->out.='</ins>';}else{$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($tb<18)if($tb<15){if($tb===12)if($node->getAttribute('type')==='1'){$this->out.='<ol class="decimal">';$this->at($node);$this->out.='</ol>';}elseif($node->getAttribute('type')==='a'){$this->out.='<ol class="alpha">';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif($tb===13){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewtopic.php?pid='.htmlspecialchars($node->getAttribute('id'),2).'#'.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewtopic.php?pid='.htmlspecialchars($node->getAttribute('id'),0).'#'.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}else{$this->out.='<div class="quotebox">';if($node->hasAttribute('author'))$this->out.='<cite>'.htmlspecialchars($node->getAttribute('author'),0).' '.htmlspecialchars($this->params['L_WROTE'],0).'</cite>';$this->out.='<blockquote><div>';$this->at($node);$this->out.='</div></blockquote></div>';}}elseif($tb===15){$this->out.='<span class="bbs">';$this->at($node);$this->out.='</span>';}elseif($tb===16){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'viewtopic.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'viewtopic.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}else{$this->out.='<span class="bbu">';$this->at($node);$this->out.='</span>';}elseif($tb<21)if($tb===18){$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'" rel="nofollow">';if($this->xpath->evaluate('text()=@url and.=@url and 55<string-length(.)- string-length(st)- string-length(et)',$node))$this->out.=htmlspecialchars($this->xpath->evaluate('substring(.,1,39)',$node),0).' … '.htmlspecialchars($this->xpath->evaluate('substring(.,string-length(.)- 10)',$node),0);else$this->at($node);$this->out.='</a>';}elseif($tb===19){$this->out.='<a href="'.htmlspecialchars($this->params['BASE_URL'],2).'profile.php?id='.htmlspecialchars($node->getAttribute('id'),2).'">';if($this->xpath->evaluate('text()=@id',$node))$this->out.=htmlspecialchars($this->params['BASE_URL'],0).'profile.php?id='.htmlspecialchars($node->getAttribute('id'),0);else$this->at($node);$this->out.='</a>';}else$this->out.='<br/>';elseif($tb===21);else{$this->out.='<p>';$this->at($node);$this->out.='</p>';}
				}
	}
}