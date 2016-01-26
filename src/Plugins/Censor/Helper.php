<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;
class Helper
{
	public $allowed;
	public $attrName = 'with';
	public $defaultReplacement = '****';
	public $regexp = '/(?!)/';
	public $replacements = [];
	public $tagName = 'CENSOR';
	public function __construct(array $config)
	{
		foreach ($config as $k => $v)
			$this->$k = $v;
	}
	public function censorHtml($html, $censorAttributes = \false)
	{
		$attributesExpr = '';
		if ($censorAttributes)
			$attributesExpr = '|"(?> [-\\w]+="[^"]*")*\\/?>';
		$delim  = $this->regexp[0];
		$pos    = \strrpos($this->regexp, $delim);
		$regexp = $delim
		        . '(?<!&#)(?<!&)'
		        . \substr($this->regexp, 1, $pos - 1)
		        . '(?=[^<">]*(?=<|$' . $attributesExpr . '))'
		        . \substr($this->regexp, $pos);
		return \preg_replace_callback(
			$regexp,
			function ($m)
			{
				return \htmlspecialchars($this->getReplacement($m[0]), \ENT_QUOTES);
			},
			$html
		);
	}
	public function censorText($text)
	{
		return \preg_replace_callback(
			$this->regexp,
			function ($m)
			{
				return $this->getReplacement($m[0]);
			},
			$text
		);
	}
	public function isCensored($word)
	{
		return (\preg_match($this->regexp, $word) && !$this->isAllowed($word));
	}
	public function reparse($xml)
	{
		if (\strpos($xml, '</' . $this->tagName . '>') !== \false)
		{
			$xml = \preg_replace_callback(
				'#<' . $this->tagName . '[^>]*>([^<]+)</' . $this->tagName . '>#',
				function ($m)
				{
					return ($this->isCensored($m[1])) ? $this->buildTag($m[1]) : $m[1];
				},
				$xml
			);
		}
		$delim  = $this->regexp[0];
		$pos    = \strrpos($this->regexp, $delim);
		$regexp = $delim
		        . '(?<!&)'
		        . \substr($this->regexp, 1, $pos - 1)
		        . '(?=[^<">]*<(?!\\/(?-i)' . $this->tagName . '>))'
		        . \substr($this->regexp, $pos);
		$xml = \preg_replace_callback(
			$regexp,
			function ($m)
			{
				return ($this->isAllowed($m[0])) ? $m[0] : $this->buildTag($m[0]);
			},
			$xml,
			-1,
			$cnt
		);
		if ($cnt > 0 && $xml[1] === 't')
		{
			$xml[1] = 'r';
			$xml[\strlen($xml) - 2] = 'r';
		}
		return $xml;
	}
	protected function buildTag($word)
	{
		$startTag = '<' . $this->tagName;
		$replacement = $this->getReplacement($word);
		if ($replacement !== $this->defaultReplacement)
			$startTag .= ' ' . $this->attrName . '="' . \htmlspecialchars($replacement, \ENT_COMPAT) . '"';
		return $startTag . '>' . $word . '</' . $this->tagName . '>';
	}
	protected function getReplacement($word)
	{
		if ($this->isAllowed($word))
			return $word;
		foreach ($this->replacements as list($regexp, $replacement))
			if (\preg_match($regexp, $word))
				return $replacement;
		return $this->defaultReplacement;
	}
	protected function isAllowed($word)
	{
		return (isset($this->allowed) && \preg_match($this->allowed, $word));
	}
}