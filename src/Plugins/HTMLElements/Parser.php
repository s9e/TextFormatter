<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$isEnd = (bool) ($text[$m[0][1] + 1] === '/');
			$pos    = $m[0][1];
			$len    = \strlen($m[0][0]);
			$elName = \strtolower($m[2 - $isEnd][0]);
			$tagName = (isset($this->config['aliases'][$elName]['']))
			         ? $this->config['aliases'][$elName]['']
			         : $this->config['prefix'] . ':' . $elName;
			if ($isEnd)
			{
				$this->parser->addEndTag($tagName, $pos, $len);
				continue;
			}
			$tag = (\substr($m[0][0], -2) === '/>')
			     ? $this->parser->addTagPair($tagName, $pos, $len, $pos + $len, 0)
			     : $this->parser->addStartTag($tagName, $pos, $len);
			$this->captureAttributes($tag, $elName, $m[3][0]);
		}
	}
	protected function captureAttributes(Tag $tag, $elName, $str)
	{
		\preg_match_all(
			'/[a-z][-a-z0-9]*(?>\\s*=\\s*(?>"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?/i',
			$str,
			$attrMatches,
			\PREG_SET_ORDER
		);
		foreach ($attrMatches as $attrMatch)
		{
			$pos = \strpos($attrMatch[0], '=');
			if ($pos === \false)
			{
				$pos = \strlen($attrMatch[0]);
				$attrMatch[0] .= '=' . \strtolower($attrMatch[0]);
			}
			$attrName  = \strtolower(\trim(\substr($attrMatch[0], 0, $pos)));
			$attrValue = \trim(\substr($attrMatch[0], 1 + $pos));
			if (isset($this->config['aliases'][$elName][$attrName]))
				$attrName = $this->config['aliases'][$elName][$attrName];
			if ($attrValue[0] === '"' || $attrValue[0] === "'")
				$attrValue = \substr($attrValue, 1, -1);
			$tag->setAttribute($attrName, \html_entity_decode($attrValue, \ENT_QUOTES, 'UTF-8'));
		}
	}
}