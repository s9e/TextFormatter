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
				$nodeName = $node->nodeName;if($nodeName==='C'){$this->out.='<code class="inline">';$this->at($node);$this->out.='</code>';}elseif($nodeName==='CODE'){$this->out.='<pre><code class="'.htmlspecialchars($node->getAttribute('lang'),2).'">';$this->at($node);$this->out.='</code></pre>';}elseif($nodeName==='DEL'||$nodeName==='html:del'){$this->out.='<del>';$this->at($node);$this->out.='</del>';}elseif($nodeName==='EM'||$nodeName==='html:em'){$this->out.='<em>';$this->at($node);$this->out.='</em>';}elseif($nodeName==='EMAIL'){$this->out.='<a href="mailto:'.htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}elseif($nodeName==='ESC')$this->out.=htmlspecialchars($this->xpath->evaluate('substring(.,2)',$node),0);elseif($nodeName==='H1'){$this->out.='<h1>';$this->at($node);$this->out.='</h1>';}elseif($nodeName==='H2'){$this->out.='<h2>';$this->at($node);$this->out.='</h2>';}elseif($nodeName==='H3'){$this->out.='<h3>';$this->at($node);$this->out.='</h3>';}elseif($nodeName==='H4'){$this->out.='<h4>';$this->at($node);$this->out.='</h4>';}elseif($nodeName==='H5'){$this->out.='<h5>';$this->at($node);$this->out.='</h5>';}elseif($nodeName==='H6'){$this->out.='<h6>';$this->at($node);$this->out.='</h6>';}elseif($nodeName==='HE')$this->out.=htmlspecialchars($node->getAttribute('char'),0);elseif($nodeName==='HR')$this->out.='<hr>';elseif($nodeName==='IMG')$this->out.='<img alt="'.htmlspecialchars($node->getAttribute('alt'),2).'" src="'.htmlspecialchars($node->getAttribute('src'),2).'" title="'.htmlspecialchars($node->getAttribute('title'),2).'">';elseif($nodeName==='LI'){$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($nodeName==='LIST')if(!$node->hasAttribute('type')){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif(strpos('upperlowerdecim',substr($node->getAttribute('type'),0,5))!==false){$this->out.='<ol style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul style="list-style-type:'.htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ul>';}elseif($nodeName==='QUOTE'){$this->out.='<blockquote>';$this->at($node);$this->out.='</blockquote>';}elseif($nodeName==='STRONG'||$nodeName==='html:strong'){$this->out.='<strong>';$this->at($node);$this->out.='</strong>';}elseif($nodeName==='SUP'||$nodeName==='html:sup'){$this->out.='<sup>';$this->at($node);$this->out.='</sup>';}elseif($nodeName==='URL'){$this->out.='<a href="'.htmlspecialchars($node->getAttribute('url'),2).'">';$this->at($node);$this->out.='</a>';}elseif($nodeName==='br')$this->out.='<br>';elseif($nodeName==='e'||$nodeName==='i'||$nodeName==='s');elseif($nodeName==='html:a'){$this->out.='<a';if($node->hasAttribute('href'))$this->out.=' href="'.htmlspecialchars($node->getAttribute('href'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';$this->at($node);$this->out.='</a>';}elseif($nodeName==='html:b'){$this->out.='<b>';$this->at($node);$this->out.='</b>';}elseif($nodeName==='html:br')$this->out.='<br>';elseif($nodeName==='html:code'){$this->out.='<code>';$this->at($node);$this->out.='</code>';}elseif($nodeName==='html:img'){$this->out.='<img';if($node->hasAttribute('src'))$this->out.=' src="'.htmlspecialchars($node->getAttribute('src'),2).'"';$this->out.='>';}elseif($nodeName==='html:s'){$this->out.='<s>';$this->at($node);$this->out.='</s>';}elseif($nodeName==='html:sub'){$this->out.='<sub>';$this->at($node);$this->out.='</sub>';}elseif($nodeName==='html:table'){$this->out.='<table>';$this->at($node);$this->out.='</table>';}elseif($nodeName==='html:tbody'){$this->out.='<tbody>';$this->at($node);$this->out.='</tbody>';}elseif($nodeName==='html:td'){$this->out.='<td';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';$this->out.='>';$this->at($node);$this->out.='</td>';}elseif($nodeName==='html:tfoot'){$this->out.='<tfoot>';$this->at($node);$this->out.='</tfoot>';}elseif($nodeName==='html:th'){$this->out.='<th';if($node->hasAttribute('colspan'))$this->out.=' colspan="'.htmlspecialchars($node->getAttribute('colspan'),2).'"';if($node->hasAttribute('rowspan'))$this->out.=' rowspan="'.htmlspecialchars($node->getAttribute('rowspan'),2).'"';if($node->hasAttribute('scope'))$this->out.=' scope="'.htmlspecialchars($node->getAttribute('scope'),2).'"';$this->out.='>';$this->at($node);$this->out.='</th>';}elseif($nodeName==='html:thead'){$this->out.='<thead>';$this->at($node);$this->out.='</thead>';}elseif($nodeName==='html:tr'){$this->out.='<tr>';$this->at($node);$this->out.='</tr>';}elseif($nodeName==='html:u'){$this->out.='<u>';$this->at($node);$this->out.='</u>';}elseif($nodeName==='p'){$this->out.='<p>';$this->at($node);$this->out.='</p>';}else $this->at($node);
			}
	}
}