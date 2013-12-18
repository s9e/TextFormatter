<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

class Helper
{
	/**
	* @var string Name of attribute used for the replacement
	*/
	public $attrName;

	/**
	* @var string Default string used to replace censored words
	*/
	public $defaultReplacement = '****';

	/**
	* @var array Array of [regexp => replacement]
	*/
	public $replacements = [];

	/**
	* @var string Name of the tag used to mark censored words
	*/
	public $tagName;

	/**
	* Constructor
	*
	* @param  array $config Helper's config
	* @return void
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
	* @param  string $text Original HTML
	* @return string       Censored HTML
	*/
	public function censorHtml($text)
	{
		// Modify the original regexp so that it only matches text nodes
		$delim  = $this->regexp[0];
		$pos    = strrpos($this->regexp, $delim);
		$regexp = substr($this->regexp, 0, $pos)
		        . '(?=[^<">]*(?><|$))'
		        . substr($this->regexp, $pos);

		return preg_replace_callback(
			$regexp,
			function ($m)
			{
				foreach ($this->replacements as list($regexp, $replacement))
				{
					if (preg_match($regexp, $m[0]))
					{
						return htmlspecialchars($replacement);
					}
				}

				return htmlspecialchars($this->defaultReplacement);
			},
			$text
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
				foreach ($this->replacements as list($regexp, $replacement))
				{
					if (preg_match($regexp, $m[0]))
					{
						return $replacement;
					}
				}

				return $this->defaultReplacement;
			},
			$text
		);
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
					return (preg_match($this->regexp, $m[1])) ? $this->buildTag($m[1]) : $m[1];
				},
				$xml
			);
		}

		// Modify the original regexp so that it only matches text nodes, and only outside of the
		// tags used by this plugin
		$delim  = $this->regexp[0];
		$pos    = strrpos($this->regexp, $delim);
		$regexp = substr($this->regexp, 0, $pos)
		        . '(?=[^<">]*<(?!\\/(?-i)' . $this->tagName . '>))'
		        . substr($this->regexp, $pos);

		$xml = preg_replace_callback(
			$regexp,
			function ($m)
			{
				return $this->buildTag($m[0]);
			},
			$xml,
			-1,
			$cnt
		);

		// If we've censored a word, ensure that the root node is r, not t
		if ($cnt && $xml[1] === 't')
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

		foreach ($this->replacements as list($regexp, $replacement))
		{
			if (preg_match($regexp, $word))
			{
				$startTag .= ' ' . $this->attrName . '="' . htmlspecialchars($replacement, ENT_QUOTES) . '"';

				break;
			}
		}

		return $startTag . '>' . $word . '</' . $this->tagName . '>';
	}
}