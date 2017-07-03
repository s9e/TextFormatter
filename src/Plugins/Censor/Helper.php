<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;
class Helper
{
	public $allowed;
	public $attrName = 'with';
	public $defaultReplacement = '****';
	public $regexp = '/(?!)/';
	public $regexpHtml = '/(?!)/';
	public $replacements = array();
	public $tagName = 'CENSOR';
	public function __construct(array $config)
	{
		foreach ($config as $k => $v)
			$this->$k = $v;
	}
	public function censorHtml($html, $censorAttributes = \false)
	{
		$_this = $this;
		$attributesExpr = '';
		if ($censorAttributes)
			$attributesExpr = '|[^<">]*+(?=<|$|"(?> [-\\w]+="[^"]*+")*+\\/?>)';
		$delim  = $this->regexpHtml[0];
		$pos    = \strrpos($this->regexpHtml, $delim);
		$regexp = $delim
		        . '(?<!&#)(?<!&)'
		        . \substr($this->regexpHtml, 1, $pos - 1)
		        . '(?=[^<>]*+(?=<|$)' . $attributesExpr . ')'
		        . \substr($this->regexpHtml, $pos);
		return \preg_replace_callback(
			$regexp,
			function ($m) use ($_this)
			{
				return \htmlspecialchars($_this->getReplacement(\html_entity_decode($m[0], \ENT_QUOTES, 'UTF-8')), \ENT_QUOTES);
			},
			$html
		);
	}
	public function censorText($text)
	{
		$_this = $this;
		return \preg_replace_callback(
			$this->regexp,
			function ($m) use ($_this)
			{
				return $_this->getReplacement($m[0]);
			},
			$text
		);
	}
	public function isCensored($word)
	{
		return (\preg_match($this->regexp, $word) && !$this->isAllowed($word));
	}
	public function buildTag($word)
	{
		$startTag = '<' . $this->tagName;
		$replacement = $this->getReplacement($word);
		if ($replacement !== $this->defaultReplacement)
			$startTag .= ' ' . $this->attrName . '="' . \htmlspecialchars($replacement, \ENT_COMPAT) . '"';
		return $startTag . '>' . $word . '</' . $this->tagName . '>';
	}
	public function getReplacement($word)
	{
		if ($this->isAllowed($word))
			return $word;
		foreach ($this->replacements as $_23be09c)
		{
			list($regexp, $replacement) = $_23be09c;
			if (\preg_match($regexp, $word))
				return $replacement;
		}
		return $this->defaultReplacement;
	}
	public function isAllowed($word)
	{
		return (isset($this->allowed) && \preg_match($this->allowed, $word));
	}
}