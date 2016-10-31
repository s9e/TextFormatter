<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

class Helper
{
	/**
	* @var string Regexp matching whitelisted words
	*/
	public $allowed;

	/**
	* @var string Name of attribute used for the replacement
	*/
	public $attrName = 'with';

	/**
	* @var string Default string used to replace censored words
	*/
	public $defaultReplacement = '****';

	/**
	* @var string Regexp matching blacklisted words
	*/
	public $regexp = '/(?!)/';

	/**
	* @var array Array of [regexp => replacement]
	*/
	public $replacements = [];

	/**
	* @var string Name of the tag used to mark censored words
	*/
	public $tagName = 'CENSOR';

	/**
	* Constructor
	*
	* @param  array $config Helper's config
	*/
	public function __construct(array $config)
	{
		foreach ($config as $k => $v)
		{
			$this->$k = $v;
		}
	}

	/**
	* Censor text nodes inside of HTML code
	*
	* NOTE: will only recognize attributes that are enclosed in double quotes
	*
	* @param  string $html             Original HTML
	* @param  bool   $censorAttributes Whether to censor the content of attributes
	* @return string                   Censored HTML
	*/
	public function censorHtml($html, $censorAttributes = false)
	{
		$attributesExpr = '';
		if ($censorAttributes)
		{
			$attributesExpr = '|"(?> [-\\w]+="[^"]*")*\\/?>';
		}

		// Modify the original regexp so that it only matches text nodes and optionally attribute
		// values
		$delim  = $this->regexp[0];
		$pos    = strrpos($this->regexp, $delim);
		$regexp = $delim
		        . '(?<!&#)(?<!&)'
		        . substr($this->regexp, 1, $pos - 1)
		        . '(?=[^<>]*(?=<|$' . $attributesExpr . '))'
		        . substr($this->regexp, $pos);

		return preg_replace_callback(
			$regexp,
			function ($m)
			{
				return htmlspecialchars($this->getReplacement($m[0]), ENT_QUOTES);
			},
			$html
		);
	}

	/**
	* Censor given plain text
	*
	* @param  string $text Original text
	* @return string       Censored text
	*/
	public function censorText($text)
	{
		return preg_replace_callback(
			$this->regexp,
			function ($m)
			{
				return $this->getReplacement($m[0]);
			},
			$text
		);
	}

	/**
	* Test whether given word is censored
	*
	* @param  string $word
	* @return bool
	*/
	public function isCensored($word)
	{
		return (preg_match($this->regexp, $word) && !$this->isAllowed($word));
	}

	/**
	* Update an intermediate representation to match a different list of words
	*
	* Will remove tags from words that are not censored anymore and will match new censored words in
	* found in plain text. Can serve as a "quick patch" to update old parsed texts to a new list of
	* words, but is not guaranteed to produce the same output as unparsing and properly reparsing
	* the whole text as new.
	*
	* Will NOT:
	*  - match new words in attribute values
	*  - match HTML entities
	*  - respect the denyChild/denyDescendant/ignoreTags rules
	*
	* @param  string $xml Intermediate representation
	* @return string      Updated intermediate representation
	*/
	public function reparse($xml)
	{
		// Test whether any words are already censored
		if (strpos($xml, '</' . $this->tagName . '>') !== false)
		{
			// Match all the tags used by this plugin and test whether their content matches the new
			// regexp. If so, preserve the tag. If not, remove the tag (preserve its content)
			$xml = preg_replace_callback(
				'#<' . $this->tagName . '[^>]*>([^<]+)</' . $this->tagName . '>#',
				function ($m)
				{
					return ($this->isCensored($m[1])) ? $this->buildTag($m[1]) : $m[1];
				},
				$xml
			);
		}

		// Modify the original regexp so that it only matches text nodes, and only outside of the
		// tags used by this plugin
		$delim  = $this->regexp[0];
		$pos    = strrpos($this->regexp, $delim);
		$regexp = $delim
		        . '(?<!&)'
		        . substr($this->regexp, 1, $pos - 1)
		        . '(?=[^<">]*<(?!\\/(?-i)' . $this->tagName . '>))'
		        . substr($this->regexp, $pos);

		$xml = preg_replace_callback(
			$regexp,
			function ($m)
			{
				return ($this->isAllowed($m[0])) ? $m[0] : $this->buildTag($m[0]);
			},
			$xml,
			-1,
			$cnt
		);

		// If we've censored a word, ensure that the root node is r, not t
		if ($cnt > 0 && $xml[1] === 't')
		{
			$xml[1] = 'r';
			$xml[strlen($xml) - 2] = 'r';
		}

		return $xml;
	}

	/**
	* Build and return the censor tag that matches given word
	*
	* @param  string $word Word to censor
	* @return string       Censor tag, complete with its replacement attribute
	*/
	protected function buildTag($word)
	{
		$startTag = '<' . $this->tagName;
		$replacement = $this->getReplacement($word);
		if ($replacement !== $this->defaultReplacement)
		{
			$startTag .= ' ' . $this->attrName . '="' . htmlspecialchars($replacement, ENT_COMPAT) . '"';
		}

		return $startTag . '>' . $word . '</' . $this->tagName . '>';
	}

	/**
	* Get the replacement for given word
	*
	* @param  string $word Original word
	* @return string       Replacement if the word is censored, or the original word otherwise
	*/
	protected function getReplacement($word)
	{
		if ($this->isAllowed($word))
		{
			return $word;
		}

		foreach ($this->replacements as list($regexp, $replacement))
		{
			if (preg_match($regexp, $word))
			{
				return $replacement;
			}
		}

		return $this->defaultReplacement;
	}

	/**
	* Test whether given word is allowed (whitelisted)
	*
	* @param  string $word
	* @return bool
	*/
	protected function isAllowed($word)
	{
		return (isset($this->allowed) && preg_match($this->allowed, $word));
	}
}